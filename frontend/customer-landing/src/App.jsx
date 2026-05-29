import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, Navigate, Route, Routes, useLocation, useNavigate, useParams } from 'react-router-dom';
import {
    cancelCustomerBooking,
    checkBookingAvailability,
    createBookingPayment,
    createCustomerBooking,
    getBranchDetail,
    getBranches,
    getCustomerBooking,
    getCustomerBookings,
    getLocations,
    loginCustomer,
    logoutCustomer,
    registerCustomer,
    refreshBookingPaymentStatus,
    resolveAssetUrl,
    validateCoupon,
} from './api.js';
import { AuthPage } from './components/customer/AuthPage.jsx';
import { BookingProgress } from './components/customer/BookingProgress.jsx';
import { BranchDetailContent } from './components/customer/BranchDetailContent.jsx';
import { CustomerFooter } from './components/customer/Footer.jsx';
import { LandingContent } from './components/customer/LandingContent.jsx';
import { MenuRoutePage } from './components/customer/MenuRoutePage.jsx';
import { SearchResultsContent } from './components/customer/SearchResultsContent.jsx';
import { CustomerTopbar } from './components/customer/Topbar.jsx';
import { Icon } from './components/Icons.jsx';
import { heroImage, partnerImage } from './data/content.js';
import { customerDateLocale, useLocalizedCustomerText } from './i18n.jsx';

const authStorageKey = 'glowhub_customer_auth';
const bookingDraftStorageKey = 'glowhub_customer_booking_draft';
const paymentDraftStorageKey = 'glowhub_customer_payment_draft';
const tones = ['rose', 'amber', 'sky', 'teal', 'orange', 'yellow', 'blue', 'slate'];
const catalogRefreshMs = 12000;
const availabilityRefreshMs = 5000;
const bookingRefreshMs = 10000;

const findServiceRoute = '/findservice';
const serviceRoute = (branchId) => (branchId ? `${findServiceRoute}/${branchId}/services` : findServiceRoute);
const deviceLocationRadiusKm = 25;
const deviceLocationMaxAccuracyMeters = deviceLocationRadiusKm * 1000;
const deviceLocationDraftMaxAgeMs = 10 * 60 * 1000;
const pastBookingDateMessage = 'Service is not available before today. Choose today or a later date.';
const locationAccuracyMessage = 'Location accuracy is low. Turn on precise location or type your city manually.';
const dateInputPattern = /^\d{4}-\d{2}-\d{2}$/;
const midtransPaymentChannels = [
    { id: 'qris', label: 'QRIS', detail: 'Scan QR from any QRIS-ready wallet or mobile banking app.' },
    { id: 'bca_va', label: 'BCA VA', detail: 'Pay with BCA mobile, ATM, or internet banking.' },
    { id: 'bni_va', label: 'BNI VA', detail: 'Supports BNI and interbank transfer.' },
    { id: 'bri_va', label: 'BRI VA', detail: 'Use BRImo, ATM, or transfer from another bank.' },
    { id: 'permata_va', label: 'Permata VA', detail: 'Pay through Permata or ATM Bersama network.' },
    { id: 'mandiri_bill', label: 'Mandiri Bill', detail: 'Use biller code and bill key from Midtrans.' },
];
const defaultGuestDetails = {
    title: '',
    firstName: '',
    lastName: '',
    email: '',
    phone: '',
    cardNumber: '',
    expMonth: '',
    expYear: '',
    cvv: '',
    cardName: '',
};

function readStoredJson(key) {
    if (typeof window === 'undefined') return null;

    try {
        const value = window.localStorage.getItem(key);
        return value ? JSON.parse(value) : null;
    } catch {
        window.localStorage.removeItem(key);
        return null;
    }
}

function writeStoredJson(key, value) {
    if (typeof window === 'undefined') return;

    try {
        window.localStorage.setItem(key, JSON.stringify(value));
    } catch {
        // Ignore storage quota/private-mode failures; the live booking state still works.
    }
}

function clearStoredJson(key) {
    if (typeof window === 'undefined') return;
    window.localStorage.removeItem(key);
}

function plainObject(value) {
    return value && typeof value === 'object' && !Array.isArray(value) ? value : null;
}

function safeString(value) {
    return typeof value === 'string' ? value : '';
}

function safeArray(value) {
    return Array.isArray(value) ? value : [];
}

function compactStoredModel(value) {
    const model = plainObject(value);

    if (!model) return null;

    const { raw, ...snapshot } = model;
    return snapshot;
}

function compactStoredArray(value) {
    return safeArray(value).map(compactStoredModel).filter(Boolean);
}

function todayInputValue() {
    const today = new Date();
    today.setMinutes(today.getMinutes() - today.getTimezoneOffset());

    return today.toISOString().slice(0, 10);
}

function isPastBookingDate(value) {
    const dateValue = String(value || '').trim();

    return dateInputPattern.test(dateValue) && dateValue < todayInputValue();
}

function normalizeDraftBookingDate(value) {
    const dateValue = String(value || '').trim();

    if (!dateInputPattern.test(dateValue) || isPastBookingDate(dateValue)) {
        return todayInputValue();
    }

    return dateValue;
}

function normalizeDraftPaymentType(value) {
    return ['dp', 'full_payment', 'pay_at_salon'].includes(value) ? value : 'pay_at_salon';
}

function normalizeDraftPaymentChannel(value) {
    return midtransPaymentChannels.some((channel) => channel.id === value) ? value : 'qris';
}

function normalizeDraftBookingType(value) {
    return value === 'queue' ? 'queue' : 'scheduled';
}

function normalizeSearchCoordinates(value) {
    const coordinates = plainObject(value);

    if (!coordinates) return null;

    const lat = Number(coordinates.lat);
    const lng = Number(coordinates.lng);
    const accuracy = Number(coordinates.accuracy);
    const timestamp = Number(coordinates.timestamp);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
    if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return null;

    return {
        lat,
        lng,
        ...(Number.isFinite(accuracy) ? { accuracy } : {}),
        ...(Number.isFinite(timestamp) ? { timestamp } : {}),
    };
}

function readBookingDraft() {
    const draft = plainObject(readStoredJson(bookingDraftStorageKey));

    if (!draft) return {};

    const bookingDate = normalizeDraftBookingDate(draft.bookingDate);
    const storedDateWasPast = isPastBookingDate(draft.bookingDate);
    const branchQueryParams = { ...(plainObject(draft.branchQueryParams) || {}) };
    const selectedLocation = plainObject(draft.selectedLocation);
    const searchCoordinates = normalizeSearchCoordinates(draft.searchCoordinates);
    const currentLocationDraft = isCurrentLocationLabel(draft.locationQuery) || isCurrentLocationLabel(selectedLocation?.label);
    const updatedAt = Number(draft.updatedAt || 0);
    const draftAge = Number.isFinite(updatedAt) ? Date.now() - updatedAt : Infinity;
    const staleDeviceLocation = currentLocationDraft && (
        !searchCoordinates
        || !locationCoordinatesArePrecise(searchCoordinates)
        || draftAge > deviceLocationDraftMaxAgeMs
    );
    const normalizedSelectedLocation = selectedLocation && currentLocationDraft
        ? { ...selectedLocation, label: 'Current location' }
        : selectedLocation;
    const normalizedLocationQuery = currentLocationDraft ? 'Current location' : safeString(draft.locationQuery);

    if (isPastBookingDate(branchQueryParams.booking_date)) {
        delete branchQueryParams.booking_date;
    }

    return {
        selectedLocation: staleDeviceLocation ? null : normalizedSelectedLocation,
        selectedBranch: staleDeviceLocation ? null : plainObject(draft.selectedBranch),
        services: staleDeviceLocation ? [] : safeArray(draft.services),
        staffs: staleDeviceLocation ? [] : safeArray(draft.staffs),
        selectedServiceIds: staleDeviceLocation ? [] : safeArray(draft.selectedServiceIds),
        bookingType: normalizeDraftBookingType(draft.bookingType),
        selectedStaffId: staleDeviceLocation ? '' : safeString(draft.selectedStaffId),
        bookingDate,
        startTime: storedDateWasPast || staleDeviceLocation ? '' : safeString(draft.startTime),
        paymentType: normalizeDraftPaymentType(draft.paymentType),
        notes: safeString(draft.notes),
        locationQuery: staleDeviceLocation ? '' : normalizedLocationQuery,
        searchCoordinates: staleDeviceLocation ? null : searchCoordinates,
        serviceCategory: safeString(draft.serviceCategory) || 'all',
        branchQueryParams: staleDeviceLocation ? {} : branchQueryParams,
    };
}

function writeBookingDraft(draft) {
    writeStoredJson(bookingDraftStorageKey, {
        version: 1,
        updatedAt: Date.now(),
        ...draft,
    });
}

function clearBookingDraft() {
    clearStoredJson(bookingDraftStorageKey);
}

function normalizeGuestDetailsDraft(value) {
    const draft = plainObject(value) || {};
    const allowed = Object.keys(defaultGuestDetails).reduce((fields, key) => ({
        ...fields,
        [key]: safeString(draft[key]),
    }), {});

    return {
        ...defaultGuestDetails,
        ...allowed,
        cardNumber: '',
        cvv: '',
    };
}

function readPaymentDraft() {
    const draft = plainObject(readStoredJson(paymentDraftStorageKey));

    if (!draft) {
        return {
            guestDetails: defaultGuestDetails,
            requests: [],
            couponCode: '',
            paymentChannel: 'qris',
        };
    }

    return {
        guestDetails: normalizeGuestDetailsDraft(draft.guestDetails),
        requests: safeArray(draft.requests).filter((request) => typeof request === 'string'),
        couponCode: safeString(draft.couponCode),
        paymentChannel: normalizeDraftPaymentChannel(draft.paymentChannel),
    };
}

function writePaymentDraft(draft) {
    const guestDetails = normalizeGuestDetailsDraft(draft.guestDetails);

    writeStoredJson(paymentDraftStorageKey, {
        version: 1,
        updatedAt: Date.now(),
        ...draft,
        guestDetails,
    });
}

function clearPaymentDraft() {
    clearStoredJson(paymentDraftStorageKey);
}

function formatPrice(price) {
    return `Rp${Number(price || 0).toLocaleString('id-ID')}`;
}

function formatDate(date) {
    if (!date) return '-';

    return new Intl.DateTimeFormat(customerDateLocale(), {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));
}

function formatTime(time) {
    if (!time) return '-';
    const match = String(time).match(/(\d{2}):(\d{2})/);
    return match ? `${match[1]}:${match[2]}` : String(time);
}

function formatWaitLabel(label) {
    return String(label || '').replace(/\bmenit\b/gi, 'minutes');
}

function stripHtml(value) {
    return String(value || '').replace(/<[^>]*>/g, '');
}

function normalizeSearchText(value) {
    return String(value || '').trim().replace(/\s+/g, ' ').toLowerCase();
}

function findMatchingLocation(locations, keyword) {
    const normalizedKeyword = normalizeSearchText(keyword);

    if (!normalizedKeyword) return null;

    return locations.find((location) => {
        const label = normalizeSearchText(location.label);
        const city = normalizeSearchText(location.city);
        const state = normalizeSearchText(location.state);
        const country = normalizeSearchText(location.country);

        return [label, city, state, country].some((value) => value === normalizedKeyword);
    }) || null;
}

function isDeviceLocationKeyword(keyword) {
    const normalizedKeyword = normalizeSearchText(keyword);

    return isCurrentLocationLabel(normalizedKeyword)
        || ['near me', 'device location', 'lokasi saya', 'lokasi saat ini'].includes(normalizedKeyword);
}

function isCurrentLocationLabel(value) {
    const normalizedValue = normalizeSearchText(value);

    return normalizedValue.startsWith('current location')
        || normalizedValue.startsWith('lokasi saat ini');
}

function locationCoordinatesArePrecise(coordinates) {
    const accuracy = Number(coordinates?.accuracy);

    return Number.isFinite(accuracy) && accuracy <= deviceLocationMaxAccuracyMeters;
}

function normalizeDevicePosition(position) {
    const lat = Number(position?.coords?.latitude);
    const lng = Number(position?.coords?.longitude);
    const accuracy = Number(position?.coords?.accuracy);
    const timestamp = Number(position?.timestamp || Date.now());

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
    if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return null;

    return {
        lat,
        lng,
        accuracy: Number.isFinite(accuracy) ? accuracy : Infinity,
        timestamp,
    };
}

function getDeviceCoordinates() {
    return new Promise((resolve) => {
        if (typeof navigator === 'undefined' || !navigator.geolocation) {
            resolve(null);
            return;
        }

        const attempts = [
            { enableHighAccuracy: true, maximumAge: 0, timeout: 16000 },
            { enableHighAccuracy: false, maximumAge: 0, timeout: 9000 },
        ];
        let bestCoordinates = null;

        function runAttempt(index = 0) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const coordinates = normalizeDevicePosition(position);

                    if (coordinates && (!bestCoordinates || coordinates.accuracy < bestCoordinates.accuracy)) {
                        bestCoordinates = coordinates;
                    }

                    if (coordinates && locationCoordinatesArePrecise(coordinates)) {
                        resolve(coordinates);
                        return;
                    }

                    if (index + 1 < attempts.length) {
                        runAttempt(index + 1);
                        return;
                    }

                    resolve(bestCoordinates && locationCoordinatesArePrecise(bestCoordinates) ? bestCoordinates : null);
                },
                () => {
                    if (index + 1 < attempts.length) {
                        runAttempt(index + 1);
                        return;
                    }

                    resolve(bestCoordinates && locationCoordinatesArePrecise(bestCoordinates) ? bestCoordinates : null);
                },
                attempts[index]
            );
        }

        runAttempt();
    });
}

function locationBranchParams(location) {
    if (!location || typeof location !== 'object') return {};

    const params = {
        country: location.country,
        state: location.state,
        city: location.city,
    };

    return Object.fromEntries(Object.entries(params).filter(([, value]) => Boolean(value)));
}

function firstName(name) {
    return String(name || 'Customer').trim().split(/\s+/)[0] || 'Customer';
}

function statusLabel(status) {
    const labels = {
        open: 'Open',
        pending: 'Pending',
        pending_payment: 'Pending Payment',
        confirmed: 'Confirmed',
        waiting: 'Waiting',
        checked_in: 'Checked In',
        inprogress: 'In Progress',
        in_progress: 'In Progress',
        completed: 'Completed',
        order_completed: 'Completed',
        provider_cancelled: 'Cancelled',
        customer_cancelled: 'Cancelled',
        cancelled: 'Cancelled',
        no_show: 'No-show',
        rescheduled: 'Rescheduled',
    };

    return labels[status] || 'Pending';
}

function paymentTypeLabel(type) {
    const labels = {
        dp: 'Down payment',
        full_payment: 'Full payment',
        pay_at_salon: 'Pay at salon',
    };

    return labels[type] || 'Pay at salon';
}

function paymentStatusLabel(status) {
    const labels = {
        paid: 'Paid',
        pending: 'Pending',
        unpaid: 'Unpaid',
        failed: 'Failed',
        refunded: 'Refunded',
        cancelled: 'Cancelled',
    };

    return labels[status] || statusLabel(status);
}

const cancelledBookingStatuses = ['cancelled', 'customer_cancelled', 'provider_cancelled', 'no_show'];
const finalBookingStatuses = [...cancelledBookingStatuses, 'completed', 'order_completed'];

function bookingIsCancelled(booking) {
    return cancelledBookingStatuses.includes(booking?.status);
}

function bookingDisplayStatus(booking) {
    const payment = booking?.payment || {};
    const bookingStatus = booking?.status || 'pending';
    const paymentType = payment.payment_type || booking?.payment_type || 'pay_at_salon';
    const paymentStatus = payment.status || booking?.payment_status || '';

    if (finalBookingStatuses.includes(bookingStatus)) {
        return bookingStatus;
    }

    if (paymentType !== 'pay_at_salon' && ['pending', 'unpaid'].includes(paymentStatus)) {
        return 'pending_payment';
    }

    return bookingStatus;
}

function bookingEffectivePaymentStatus(booking) {
    if (bookingIsCancelled(booking)) return 'cancelled';

    const payment = booking?.payment || {};

    return payment.status || booking?.payment_status || 'pending';
}

function bookingHeroVisual(booking) {
    const effectivePaymentStatus = bookingEffectivePaymentStatus(booking);
    const displayStatus = bookingDisplayStatus(booking);

    if (bookingIsCancelled(booking) || effectivePaymentStatus === 'failed') {
        return { icon: 'x', state: 'is-cancelled' };
    }

    if (effectivePaymentStatus === 'paid') {
        return { icon: 'check', state: 'is-paid' };
    }

    if (displayStatus === 'pending_payment' || ['pending', 'unpaid'].includes(effectivePaymentStatus)) {
        return { icon: 'clock', state: 'is-pending' };
    }

    return { icon: 'calendar', state: 'is-default' };
}

function bookingServiceItems(booking) {
    if (Array.isArray(booking?.services) && booking.services.length > 0) {
        return booking.services;
    }

    return booking?.service ? [booking.service] : [];
}

function bookingBranchMeta(booking) {
    const branch = booking?.branch || {};
    const providerProfile = booking?.provider?.provider_profile
        || booking?.provider?.providerProfile
        || branch.provider?.provider_profile
        || branch.provider?.providerProfile
        || {};
    const name = branch.branch_name || branch.name || 'Salon Branch';
    const address = branch.address
        || [branch.city, branch.city_id, branch.state, branch.state_id, branch.country, branch.country_id].filter(Boolean).join(', ')
        || 'Salon address is not available';
    const image = resolveAssetUrl(
        branch.image_url
        || branch.image
        || branch.thumbnail
        || branch.photo
        || providerProfile.image_url
        || providerProfile.image
    ) || heroImage;

    return { name, address, image };
}

function bookingDateTimeLabel(booking) {
    const time = booking?.start_time || booking?.booking_time;

    return `${formatDate(booking?.booking_date)}${time ? `, ${formatTime(time)}` : ''}`;
}

function canCancelBooking(booking) {
    return ['open', 'pending', 'pending_payment', 'confirmed', 'waiting', 'checked_in', 'rescheduled'].includes(booking?.status);
}

function normalizeBranch(branch, index = 0) {
    const provider = branch.provider || {};
    const image = resolveAssetUrl(branch.image_url || branch.image) || partnerImage;
    const galleryImages = Array.isArray(branch.gallery_images)
        ? branch.gallery_images.map(resolveAssetUrl).filter(Boolean)
        : [];

    return {
        id: branch.id,
        name: branch.branch_name || 'Salon Branch',
        provider: provider.name || 'Salon',
        address: branch.address || '',
        city: branch.city_id || '',
        state: branch.state_id || '',
        country: branch.country_id || '',
        locationLabel: branch.location_label || [branch.city_id, branch.state_id, branch.country_id].filter(Boolean).join(', '),
        image,
        galleryImages: Array.from(new Set([image, ...galleryImages])).filter(Boolean),
        workingStart: String(branch.working_start_hour || '09:00').slice(0, 5),
        workingEnd: String(branch.working_end_hour || '18:00').slice(0, 5),
        workingDays: Array.isArray(branch.working_days) ? branch.working_days : [],
        latitude: branch.latitude,
        longitude: branch.longitude,
        distanceKm: branch.distance_km,
        staffCount: branch.staffs_count || branch.staff_count || 0,
        servicesCount: branch.services_count || 0,
        serviceCategories: Array.isArray(branch.service_categories) ? branch.service_categories : [],
        serviceTitles: Array.isArray(branch.service_titles) ? branch.service_titles : [],
        hasQueueService: Boolean(branch.has_queue_service),
        hasScheduledService: Boolean(branch.has_scheduled_service),
        supportsPayAtSalon: Boolean(branch.supports_pay_at_salon),
        minPrice: branch.min_price,
        nextAvailableSlot: branch.next_available_slot,
        rating: branch.rating || 4.8,
        tone: tones[index % tones.length],
        raw: branch,
    };
}

function normalizeService(service, index = 0) {
    const category = service.category_name || service.service_category?.name || service.category || 'Beauty';
    const galleryImage = Array.isArray(service.gallery_image) ? service.gallery_image[0] : service.gallery_image;

    return {
        id: service.id,
        title: service.title || 'Salon Service',
        category,
        description: stripHtml(service.description),
        price: Number(service.price || 0),
        duration: Number(service.estimated_duration || 30),
        minDuration: Number(service.minimum_duration || 0),
        maxDuration: Number(service.maximum_duration || service.estimated_duration || 60),
        isQueueEnabled: Boolean(service.is_queue_enabled ?? true),
        isScheduledEnabled: Boolean(service.is_scheduled_enabled ?? true),
        requiresDp: Boolean(service.requires_dp),
        dpAmount: service.dp_amount,
        paymentPolicy: service.payment_policy,
        image: resolveAssetUrl(service.image_url || galleryImage) || heroImage,
        tone: tones[index % tones.length],
        raw: service,
    };
}

function normalizeStaff(staff, index = 0) {
    return {
        id: staff.id,
        name: staff.name || `${staff.first_name || ''} ${staff.last_name || ''}`.trim() || 'Staff',
        rating: staff.rating,
        status: staff.current_status || staff.status || 'available',
        image: resolveAssetUrl(staff.image_url || staff.image),
        skills: Array.isArray(staff.skills) ? staff.skills : [],
        tone: tones[index % tones.length],
        raw: staff,
    };
}

function stableStaffList(staffList) {
    return [...staffList].sort((left, right) => {
        const leftId = Number(left.id);
        const rightId = Number(right.id);

        if (Number.isFinite(leftId) && Number.isFinite(rightId) && leftId !== rightId) {
            return leftId - rightId;
        }

        return String(left.name || '').localeCompare(String(right.name || ''));
    });
}

function bookingServicesText(booking) {
    const services = bookingServiceItems(booking);

    if (services.length > 0) {
        return services.map((service) => service.title || service.service_name || service.name || 'Service').join(', ');
    }

    return 'Service';
}

function sameId(left, right) {
    return String(left) === String(right);
}

function availabilityCacheKey(branchId, serviceIds, bookingType, bookingDate, staffId = '') {
    const servicesKey = [...serviceIds].map(String).sort().join(',');

    return [branchId || '', servicesKey, bookingType || '', bookingDate || '', staffId || 'any'].join('|');
}

function slotDateTime(dateValue, timeValue) {
    const dateMatch = String(dateValue || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
    const timeMatch = String(timeValue || '').match(/^(\d{1,2}):(\d{2})/);

    if (!dateMatch || !timeMatch) return null;

    return new Date(
        Number(dateMatch[1]),
        Number(dateMatch[2]) - 1,
        Number(dateMatch[3]),
        Number(timeMatch[1]),
        Number(timeMatch[2]),
        0,
        0
    );
}

function slotIsExpired(dateValue, timeValue, referenceDate = new Date()) {
    const slotTime = slotDateTime(dateValue, timeValue);

    return Boolean(slotTime && slotTime <= referenceDate);
}

function staffHasSelectedServiceSkills(staff, serviceIds) {
    const skills = Array.isArray(staff?.skills) ? staff.skills : [];
    const skillIds = skills
        .map((skill) => skill?.id ?? skill?.service_id ?? skill)
        .filter((id) => id !== undefined && id !== null)
        .map(String);

    if (skillIds.length === 0) return true;

    return serviceIds.every((serviceId) => skillIds.includes(String(serviceId)));
}

function App() {
    useLocalizedCustomerText();

    const navigate = useNavigate();
    const location = useLocation();
    const savedBookingDraft = useMemo(readBookingDraft, []);
    const [locations, setLocations] = useState([]);
    const [branches, setBranches] = useState([]);
    const [services, setServices] = useState(savedBookingDraft.services || []);
    const [staffs, setStaffs] = useState(stableStaffList(savedBookingDraft.staffs || []));
    const [selectedLocation, setSelectedLocation] = useState(savedBookingDraft.selectedLocation || null);
    const [selectedBranch, setSelectedBranch] = useState(savedBookingDraft.selectedBranch || null);
    const [selectedServiceIds, setSelectedServiceIds] = useState(savedBookingDraft.selectedServiceIds || []);
    const [bookingType, setBookingType] = useState(savedBookingDraft.bookingType || 'scheduled');
    const [selectedStaffId, setSelectedStaffId] = useState(savedBookingDraft.selectedStaffId || '');
    const [bookingDate, setBookingDate] = useState(savedBookingDraft.bookingDate || todayInputValue());
    const [startTime, setStartTime] = useState(savedBookingDraft.startTime || '');
    const [paymentType, setPaymentType] = useState(savedBookingDraft.paymentType || 'pay_at_salon');
    const [notes, setNotes] = useState(savedBookingDraft.notes || '');
    const [locationQuery, setLocationQuery] = useState(savedBookingDraft.locationQuery || '');
    const [searchCoordinates, setSearchCoordinates] = useState(savedBookingDraft.searchCoordinates || null);
    const [searchError, setSearchError] = useState('');
    const [serviceCategory, setServiceCategory] = useState(savedBookingDraft.serviceCategory || 'all');
    const [branchQueryParams, setBranchQueryParams] = useState(savedBookingDraft.branchQueryParams || {});
    const [catalogRefreshTick, setCatalogRefreshTick] = useState(0);
    const [availabilityRefreshTick, setAvailabilityRefreshTick] = useState(0);
    const [clockTick, setClockTick] = useState(() => Date.now());
    const isReviewBookingRoute = location.pathname === '/booking/payment';

    useEffect(() => {
        const frame = window.requestAnimationFrame(() => {
            window.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
        });

        return () => window.cancelAnimationFrame(frame);
    }, [location.pathname, location.search]);

    useEffect(() => {
        const refreshClock = () => setClockTick(Date.now());
        const interval = window.setInterval(refreshClock, 60000);

        window.addEventListener('focus', refreshClock);
        document.addEventListener('visibilitychange', refreshClock);

        return () => {
            window.clearInterval(interval);
            window.removeEventListener('focus', refreshClock);
            document.removeEventListener('visibilitychange', refreshClock);
        };
    }, []);

    const [isBooting, setBooting] = useState(true);
    const [isLoadingBranches, setLoadingBranches] = useState(false);
    const [isLoadingBranch, setLoadingBranch] = useState(false);
    const [availability, setAvailability] = useState(null);
    const [availabilityLoading, setAvailabilityLoading] = useState(false);
    const availabilityRequestRef = useRef(0);
    const availabilityKeyRef = useRef('');
    const [bookingLoading, setBookingLoading] = useState(false);
    const [bookingError, setBookingError] = useState('');
    const [bookingSuccess, setBookingSuccess] = useState(null);
    const [paymentSetup, setPaymentSetup] = useState({
        bookingId: null,
        channel: 'qris',
        status: 'idle',
        error: '',
    });

    const [authUser, setAuthUser] = useState(null);
    const [authToken, setAuthToken] = useState('');
    const [isAuthModalOpen, setAuthModalOpen] = useState(false);
    const [authModalMode, setAuthModalMode] = useState('login');
    const [authLoading, setAuthLoading] = useState(false);
    const [authError, setAuthError] = useState('');
    const [authFormErrors, setAuthFormErrors] = useState({});
    const [loginForm, setLoginForm] = useState({ email: '', password: '' });
    const [registerForm, setRegisterForm] = useState({
        name: '',
        email: '',
        phone_number: '',
        gender: '',
        password: '',
        password_confirmation: '',
    });
    const [customerBookings, setCustomerBookings] = useState([]);
    const [bookingsLoading, setBookingsLoading] = useState(false);
    const selectedBranchId = selectedBranch?.id || null;
    const activeAvailabilityKey = useMemo(() => (
        selectedBranchId && selectedServiceIds.length > 0
            ? availabilityCacheKey(
                selectedBranchId,
                selectedServiceIds,
                bookingType,
                bookingType === 'scheduled' ? bookingDate : todayInputValue(),
                selectedStaffId
            )
            : ''
    ), [bookingDate, bookingType, selectedBranchId, selectedServiceIds, selectedStaffId]);

    useEffect(() => {
        if (bookingSuccess) return;

        const draft = {
            selectedLocation: selectedLocation ? { ...selectedLocation } : null,
            selectedBranch: compactStoredModel(selectedBranch),
            services: compactStoredArray(services),
            staffs: compactStoredArray(staffs),
            selectedServiceIds,
            bookingType,
            selectedStaffId,
            bookingDate,
            startTime,
            paymentType,
            notes,
            locationQuery,
            searchCoordinates: searchCoordinates ? { ...searchCoordinates } : null,
            serviceCategory,
            branchQueryParams,
        };
        const hasDraftContent = Boolean(
            draft.selectedLocation
            || draft.selectedBranch
            || draft.services.length > 0
            || draft.staffs.length > 0
            || draft.selectedServiceIds.length > 0
            || draft.selectedStaffId
            || draft.startTime
            || draft.paymentType !== 'pay_at_salon'
            || draft.notes.trim()
            || draft.locationQuery.trim()
            || draft.searchCoordinates
            || draft.serviceCategory !== 'all'
            || Object.keys(draft.branchQueryParams).length > 0
            || draft.bookingType !== 'scheduled'
            || draft.bookingDate !== todayInputValue()
        );

        if (!hasDraftContent) {
            clearBookingDraft();
            return;
        }

        writeBookingDraft(draft);
    }, [
        bookingDate,
        bookingSuccess,
        bookingType,
        branchQueryParams,
        locationQuery,
        notes,
        paymentType,
        searchCoordinates,
        selectedBranch,
        selectedLocation,
        selectedServiceIds,
        selectedStaffId,
        serviceCategory,
        services,
        staffs,
        startTime,
    ]);

    function resetBookingSession() {
        setSelectedLocation(null);
        setSelectedBranch(null);
        setServices([]);
        setStaffs([]);
        setSelectedServiceIds([]);
        setBookingType('scheduled');
        setSelectedStaffId('');
        setBookingDate(todayInputValue());
        setStartTime('');
        setPaymentType('pay_at_salon');
        setNotes('');
        setLocationQuery('');
        setSearchCoordinates(null);
        setSearchError('');
        setServiceCategory('all');
        setBranchQueryParams({});
        setAvailability(null);
        setBookingError('');
        setBookingSuccess(null);
        setPaymentSetup({ bookingId: null, channel: 'qris', status: 'idle', error: '' });
        clearBookingDraft();
        clearPaymentDraft();
    }

    const refreshCatalog = useCallback(async (params = {}, options = {}) => {
        const { quiet = false } = options;

        if (!quiet) setLoadingBranches(true);

        try {
            const results = await getBranches({ per_page: 100, ...params });
            const normalizedBranches = results.map(normalizeBranch);

            setBranches(normalizedBranches);
            setSelectedBranch((current) => {
                if (!current) return current;

                const updatedBranch = normalizedBranches.find((branch) => sameId(branch.id, current.id));
                return updatedBranch ? { ...current, ...updatedBranch } : current;
            });

            return normalizedBranches;
        } catch {
            if (!quiet) setBranches([]);
            return [];
        } finally {
            if (!quiet) setLoadingBranches(false);
        }
    }, []);

    const refreshLocations = useCallback(async () => {
        try {
            setLocations(await getLocations({ per_page: 100 }));
        } catch {
            // Keep existing locations if the refresh fails.
        }
    }, []);

    const refreshSelectedBranch = useCallback(async (branchId, options = {}) => {
        if (!branchId) return null;

        const { quiet = false } = options;

        if (!quiet) setLoadingBranch(true);

        try {
            const detail = await getBranchDetail(branchId);

            if (!detail) return null;

            const nextBranch = normalizeBranch(detail);
            const nextServices = (detail.services || []).map(normalizeService);
            const nextStaffs = stableStaffList((detail.staff || []).map(normalizeStaff));
            const nextServiceIds = new Set(nextServices.map((service) => String(service.id)));

            setSelectedBranch(nextBranch);
            setServices(nextServices);
            setStaffs(nextStaffs);
            setSelectedServiceIds((current) => {
                const nextSelectedIds = current.filter((id) => nextServiceIds.has(String(id)));

                return nextSelectedIds.length === current.length
                    && nextSelectedIds.every((id, index) => sameId(id, current[index]))
                    ? current
                    : nextSelectedIds;
            });
            setServiceCategory((current) => {
                if (current === 'all') return current;
                return nextServices.some((service) => service.category === current) ? current : 'all';
            });

            return detail;
        } catch {
            return null;
        } finally {
            if (!quiet) setLoadingBranch(false);
        }
    }, []);

    useEffect(() => {
        if (!selectedBranchId || services.length > 0 || isLoadingBranch) return;

        void refreshSelectedBranch(selectedBranchId, { quiet: true });
    }, [isLoadingBranch, refreshSelectedBranch, selectedBranchId, services.length]);

    const refreshAvailability = useCallback(async (options = {}) => {
        const requestId = availabilityRequestRef.current + 1;
        availabilityRequestRef.current = requestId;

        if (!selectedBranchId || selectedServiceIds.length === 0 || !activeAvailabilityKey) {
            setAvailability(null);
            availabilityKeyRef.current = '';
            setAvailabilityLoading(false);
            return null;
        }

        const { quiet = false } = options;
        const keyChanged = availabilityKeyRef.current !== activeAvailabilityKey;

        if (keyChanged) {
            setAvailability(null);
            availabilityKeyRef.current = '';
        }

        if (!quiet || keyChanged) setAvailabilityLoading(true);

        try {
            const data = await checkBookingAvailability({
                branch_id: selectedBranchId,
                service_ids: selectedServiceIds,
                booking_type: bookingType,
                booking_date: bookingType === 'scheduled' ? bookingDate : undefined,
                staff_id: selectedStaffId || undefined,
            });

            if (requestId !== availabilityRequestRef.current) {
                return null;
            }

            availabilityKeyRef.current = activeAvailabilityKey;
            setAvailability({ ...data, _key: activeAvailabilityKey, received_at: Date.now() });
            return data;
        } catch {
            if (requestId === availabilityRequestRef.current && (keyChanged || !quiet)) {
                setAvailability(null);
                availabilityKeyRef.current = '';
            }

            return null;
        } finally {
            if (requestId === availabilityRequestRef.current) {
                setAvailabilityLoading(false);
            }
        }
    }, [activeAvailabilityKey, bookingDate, bookingType, selectedBranchId, selectedServiceIds, selectedStaffId]);

    useEffect(() => {
        let mounted = true;

        async function boot() {
            try {
                const [locationResult, branchResult] = await Promise.allSettled([
                    getLocations({ per_page: 100 }),
                    getBranches({ per_page: 100, ...(savedBookingDraft.branchQueryParams || {}) }),
                ]);

                if (!mounted) return;

                if (locationResult.status === 'fulfilled') {
                    setLocations(locationResult.value);
                }

                if (branchResult.status === 'fulfilled') {
                    setBranches(branchResult.value.map(normalizeBranch));
                }

            } finally {
                if (mounted) setBooting(false);
            }
        }

        boot();

        return () => {
            mounted = false;
        };
    }, []);

    useEffect(() => {
        const savedAuth = window.localStorage.getItem(authStorageKey);

        if (!savedAuth) return;

        try {
            const parsed = JSON.parse(savedAuth);
            if (parsed?.token && parsed?.user?.role === 'customer') {
                setAuthToken(parsed.token);
                setAuthUser(parsed.user);
            }
        } catch {
            window.localStorage.removeItem(authStorageKey);
        }
    }, []);

    useEffect(() => {
        if (!authToken || authUser?.role !== 'customer') {
            setCustomerBookings([]);
            return;
        }

        loadCustomerBookings(authToken);
    }, [authToken, authUser]);

    useEffect(() => {
        const refresh = () => {
            setCatalogRefreshTick((tick) => tick + 1);
            setAvailabilityRefreshTick((tick) => tick + 1);
        };
        const interval = window.setInterval(refresh, catalogRefreshMs);

        window.addEventListener('focus', refresh);
        document.addEventListener('visibilitychange', refresh);

        return () => {
            window.clearInterval(interval);
            window.removeEventListener('focus', refresh);
            document.removeEventListener('visibilitychange', refresh);
        };
    }, []);

    useEffect(() => {
        if (document.hidden) return;

        void refreshLocations();
        void refreshCatalog(branchQueryParams, { quiet: true });

        if (selectedBranchId) {
            void refreshSelectedBranch(selectedBranchId, { quiet: true });
        }
    }, [branchQueryParams, catalogRefreshTick, refreshCatalog, refreshLocations, refreshSelectedBranch, selectedBranchId]);

    useEffect(() => {
        const interval = window.setInterval(() => {
            setAvailabilityRefreshTick((tick) => tick + 1);
        }, availabilityRefreshMs);

        return () => window.clearInterval(interval);
    }, []);

    useEffect(() => {
        if (!authToken || authUser?.role !== 'customer') return undefined;

        const refreshBookings = () => {
            if (!document.hidden) void loadCustomerBookings(authToken, { quiet: true });
        };
        const interval = window.setInterval(refreshBookings, bookingRefreshMs);

        window.addEventListener('focus', refreshBookings);
        document.addEventListener('visibilitychange', refreshBookings);

        return () => {
            window.clearInterval(interval);
            window.removeEventListener('focus', refreshBookings);
            document.removeEventListener('visibilitychange', refreshBookings);
        };
    }, [authToken, authUser]);

    const selectedServices = useMemo(
        () => services.filter((service) => selectedServiceIds.some((serviceId) => sameId(serviceId, service.id))),
        [services, selectedServiceIds]
    );

    const totals = useMemo(() => ({
        price: selectedServices.reduce((sum, service) => sum + service.price, 0),
        duration: selectedServices.reduce((sum, service) => sum + service.duration, 0),
    }), [selectedServices]);

    useEffect(() => {
        if (selectedServices.length === 0) return;

        const canUseScheduled = selectedServices.every((service) => service.isScheduledEnabled);
        const canUseQueue = selectedServices.every((service) => service.isQueueEnabled);

        if (bookingType === 'scheduled' && !canUseScheduled && canUseQueue) {
            setBookingType('queue');
        }

        if (bookingType === 'queue' && !canUseQueue && canUseScheduled) {
            setBookingType('scheduled');
        }
    }, [bookingType, selectedServices]);

    const categories = useMemo(() => {
        const names = services.map((service) => service.category).filter(Boolean);
        return ['all', ...Array.from(new Set(names))];
    }, [services]);

    const filteredServices = useMemo(() => {
        if (serviceCategory === 'all') return services;
        return services.filter((service) => service.category === serviceCategory);
    }, [services, serviceCategory]);

    const fallbackEligibleStaff = useMemo(() => {
        if (selectedServiceIds.length === 0) return staffs;

        return staffs.filter((staff) => staff.status !== 'offline' && staffHasSelectedServiceSkills(staff, selectedServiceIds));
    }, [selectedServiceIds, staffs]);

    const staffOptions = useMemo(() => {
        if (selectedServiceIds.length > 0) {
            return fallbackEligibleStaff;
        }

        return staffs;
    }, [fallbackEligibleStaff, selectedServiceIds, staffs]);

    const availabilityMatchesSelection = Boolean(activeAvailabilityKey && availability?._key === activeAvailabilityKey);
    const isAvailabilityResolving = Boolean(activeAvailabilityKey) && (!availabilityMatchesSelection || availabilityLoading);
    const availableSlots = availabilityMatchesSelection ? (availability?.available_slots || []) : [];
    const queueEstimation = availabilityMatchesSelection ? availability?.queue_estimation : null;
    const slotReferenceDate = useMemo(() => new Date(clockTick), [clockTick]);
    const visibleSlots = useMemo(() => {
        const staffId = selectedStaffId ? Number(selectedStaffId) : null;
        const slots = staffId ? availableSlots.filter((slot) => Number(slot.staff_id) === staffId) : availableSlots;
        const unique = new Map();

        slots.forEach((slot) => {
            if (!unique.has(slot.time)) {
                unique.set(slot.time, {
                    ...slot,
                    expired: slotIsExpired(bookingDate, slot.time, slotReferenceDate),
                });
            }
        });

        const sortedSlots = Array.from(unique.values());
        const expiredSlots = sortedSlots.filter((slot) => slot.expired);
        const availableFutureSlots = sortedSlots.filter((slot) => !slot.expired);
        const visibleExpiredSlots = bookingDate === todayInputValue() ? expiredSlots.slice(-4) : expiredSlots;

        return [...visibleExpiredSlots, ...availableFutureSlots].slice(0, 18);
    }, [availableSlots, bookingDate, selectedStaffId, slotReferenceDate]);

    useEffect(() => {
        if (document.hidden) return;

        void refreshAvailability({ quiet: availabilityRefreshTick > 0 });
    }, [availabilityRefreshTick, refreshAvailability]);

    useEffect(() => {
        if (!selectedStaffId || selectedServiceIds.length === 0) {
            return;
        }

        const selectedStaffStillEligible = staffOptions.some((staff) => String(staff.id) === String(selectedStaffId));

        if (!selectedStaffStillEligible) {
            setSelectedStaffId('');
        }
    }, [selectedServiceIds, selectedStaffId, staffOptions]);

    useEffect(() => {
        if (isReviewBookingRoute) {
            return;
        }

        if (bookingType !== 'scheduled') {
            setStartTime('');
            return;
        }

        if (!availabilityMatchesSelection || isAvailabilityResolving) {
            return;
        }

        const nextAvailableSlot = visibleSlots.find((slot) => !slot.expired);

        if (!nextAvailableSlot) {
            if (startTime) setStartTime('');
            return;
        }

        if (!visibleSlots.some((slot) => slot.time === startTime && !slot.expired)) {
            setStartTime(nextAvailableSlot.time);
        }
    }, [availabilityMatchesSelection, bookingType, isAvailabilityResolving, isReviewBookingRoute, startTime, visibleSlots]);

    async function loadBranches(params = {}) {
        setSearchError('');
        setBranchQueryParams(params);
        setSelectedBranch(null);
        setServices([]);
        setStaffs([]);
        setSelectedServiceIds([]);
        setSelectedStaffId('');
        setAvailability(null);
        setBookingSuccess(null);
        navigate(findServiceRoute);

        return refreshCatalog(params);
    }

    function updateSearchBookingDate(value) {
        const nextDate = String(value || '').trim();

        setBookingDate(nextDate);
        setSearchError(isPastBookingDate(nextDate) ? pastBookingDateMessage : '');
    }

    async function submitLocation(event) {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const keyword = String(formData.get('location') || locationQuery).trim();
        const usesDeviceLocation = isDeviceLocationKeyword(keyword);
        const matchedLocation = usesDeviceLocation ? null : findMatchingLocation(locations, keyword);
        const selectedBookingDate = String(formData.get('booking_date') || '').trim();
        let latitude = String(formData.get('lat') || '').trim();
        let longitude = String(formData.get('lng') || '').trim();
        const hiddenCoordinatesArePrecise = Boolean(
            latitude
            && longitude
            && searchCoordinates
            && locationCoordinatesArePrecise(searchCoordinates)
            && String(searchCoordinates.lat) === latitude
            && String(searchCoordinates.lng) === longitude
        );
        let preciseCoordinates = hiddenCoordinatesArePrecise ? searchCoordinates : null;

        if (isPastBookingDate(selectedBookingDate)) {
            setSearchError(pastBookingDateMessage);
            setBookingDate(todayInputValue());
            setSearchCoordinates(null);
            setSelectedLocation(keyword && !usesDeviceLocation ? { label: keyword } : null);
            setSelectedBranch(null);
            setServices([]);
            setStaffs([]);
            setSelectedServiceIds([]);
            setSelectedStaffId('');
            setAvailability(null);
            setBranchQueryParams({ booking_date: selectedBookingDate });
            setBranches([]);
            navigate(findServiceRoute);
            return;
        }

        setSearchError('');

        const scheduleParams = {
            ...(selectedBookingDate ? { booking_date: selectedBookingDate } : {}),
        };

        if (selectedBookingDate) setBookingDate(selectedBookingDate);

        if ((!latitude || !longitude || !hiddenCoordinatesArePrecise) && (!keyword || usesDeviceLocation)) {
            const coordinates = await getDeviceCoordinates();

            if (coordinates) {
                latitude = String(coordinates.lat);
                longitude = String(coordinates.lng);
                preciseCoordinates = coordinates;
            } else {
                setSearchCoordinates(null);
                setSelectedLocation({ label: 'Current location' });
                setLocationQuery('Current location');
                setSearchError(locationAccuracyMessage);
                setBranchQueryParams({});
                setBranches([]);
                navigate(findServiceRoute);
                return;
            }
        }

        if (latitude && longitude) {
            const locationLabel = usesDeviceLocation || !keyword ? 'Current location' : keyword;
            const locationCoordinates = preciseCoordinates || normalizeSearchCoordinates({ lat: latitude, lng: longitude });

            setLocationQuery(locationLabel);
            setSearchCoordinates(locationCoordinates);
            setSelectedLocation({ label: locationLabel, ...(locationCoordinates || { lat: latitude, lng: longitude }) });
            await loadBranches({
                lat: latitude,
                lng: longitude,
                radius_km: deviceLocationRadiusKm,
                ...scheduleParams,
            });
            const resolvedLocationLabel = usesDeviceLocation || !keyword ? 'Current location' : locationLabel;

            setLocationQuery(resolvedLocationLabel);
            setSelectedLocation({ label: resolvedLocationLabel, ...(locationCoordinates || { lat: latitude, lng: longitude }) });
            return;
        }

        if (matchedLocation) {
            setSearchCoordinates(null);
            setSelectedLocation(matchedLocation);
            await loadBranches({ ...locationBranchParams(matchedLocation), ...scheduleParams });
            return;
        }

        const shouldSearchKeyword = keyword && !usesDeviceLocation;

        setSearchCoordinates(null);
        setSelectedLocation(shouldSearchKeyword ? { label: keyword } : null);
        await loadBranches({ ...(shouldSearchKeyword ? { search: keyword } : {}), ...scheduleParams });
    }

    async function useCurrentLocation(options = {}) {
        const { refreshResults = false } = options;
        const loadingLabel = 'Finding location...';

        setSearchError('');
        setLocationQuery(loadingLabel);
        setSelectedLocation({ label: loadingLabel });

        const coordinates = await getDeviceCoordinates();

        if (!coordinates) {
            setSearchCoordinates(null);
            setLocationQuery('Current location');
            setSelectedLocation({ label: 'Current location' });
            setSearchError(locationAccuracyMessage);
            if (refreshResults) {
                setBranchQueryParams({});
                setSelectedBranch(null);
                setBranches([]);
                setServices([]);
                setStaffs([]);
                setSelectedServiceIds([]);
                setSelectedStaffId('');
                setAvailability(null);
                setBookingSuccess(null);
                navigate(findServiceRoute);
            }
            return null;
        }

        const currentCoordinates = coordinates;
        const resolvedLocationLabel = 'Current location';

        setSearchCoordinates(currentCoordinates);
        setLocationQuery(resolvedLocationLabel);
        setSelectedLocation({ label: resolvedLocationLabel, ...currentCoordinates });

        if (refreshResults) {
            const selectedBookingDate = isPastBookingDate(bookingDate) ? todayInputValue() : (bookingDate || todayInputValue());

            if (selectedBookingDate !== bookingDate) {
                setBookingDate(selectedBookingDate);
            }

            await loadBranches({
                lat: currentCoordinates.lat,
                lng: currentCoordinates.lng,
                radius_km: deviceLocationRadiusKm,
                ...(selectedBookingDate ? { booking_date: selectedBookingDate } : {}),
            });
        }

        return currentCoordinates;
    }

    async function chooseBranch(branch) {
        setSelectedBranch(branch);
        setSelectedServiceIds([]);
        setSelectedStaffId('');
        setAvailability(null);
        setBookingSuccess(null);
        setBookingError('');
        navigate(serviceRoute(branch.id));

        try {
            const detail = await refreshSelectedBranch(branch.id, { quiet: true });

            if (!detail) {
                setSelectedBranch(branch);
            }
        } catch {
            setSelectedBranch(branch);
        }
    }

    function toggleService(serviceId) {
        setSelectedServiceIds((current) => {
            if (current.some((id) => sameId(id, serviceId))) {
                return current.filter((id) => !sameId(id, serviceId));
            }

            return [...current, serviceId];
        });
    }

    function openAuthModal(mode) {
        setAuthError('');
        setAuthFormErrors({});
        setAuthModalOpen(false);
        navigate(mode === 'register' ? '/signup' : '/signin');
    }

    function closeAuthModal() {
        if (!authLoading) setAuthModalOpen(false);
    }

    function authFieldError(field) {
        return Array.isArray(authFormErrors[field]) ? authFormErrors[field][0] : '';
    }

    function rememberCustomer(payload) {
        if (payload?.user?.role !== 'customer') {
            throw new Error('Use a customer account.');
        }

        const authPayload = { user: payload.user, token: payload.token };
        window.localStorage.setItem(authStorageKey, JSON.stringify(authPayload));
        setAuthUser(payload.user);
        setAuthToken(payload.token);
    }

    async function handleLoginSubmit(event) {
        event.preventDefault();
        setAuthLoading(true);
        setAuthError('');
        setAuthFormErrors({});

        try {
            const response = await loginCustomer(loginForm);
            rememberCustomer(response);
            setAuthModalOpen(false);
            navigate('/');
        } catch (error) {
            setAuthError(error.message || 'Login failed.');
            setAuthFormErrors(error.errors || {});
        } finally {
            setAuthLoading(false);
        }
    }

    async function handleRegisterSubmit(event) {
        event.preventDefault();
        setAuthLoading(true);
        setAuthError('');
        setAuthFormErrors({});

        try {
            const response = await registerCustomer({
                name: registerForm.name,
                email: registerForm.email,
                phone_number: registerForm.phone_number || undefined,
                gender: registerForm.gender || undefined,
                password: registerForm.password,
                password_confirmation: registerForm.password_confirmation,
            });
            rememberCustomer(response);
            setAuthModalOpen(false);
            navigate('/');
        } catch (error) {
            setAuthError(error.message || 'Registration failed.');
            setAuthFormErrors(error.errors || {});
        } finally {
            setAuthLoading(false);
        }
    }

    async function handleLogout() {
        const token = authToken;

        setAuthUser(null);
        setAuthToken('');
        setCustomerBookings([]);
        window.localStorage.removeItem(authStorageKey);
        resetBookingSession();
        navigate('/', { replace: true });

        if (token) {
            try {
                await logoutCustomer(token);
            } catch {
                // Local logout already completed.
            }
        }
    }

    async function submitBooking(options = {}) {
        if (!authUser || !authToken) {
            openAuthModal('login');
            return;
        }

        if (!selectedBranch || selectedServiceIds.length === 0) return;

        setBookingLoading(true);
        setBookingError('');
        setPaymentSetup({
            bookingId: null,
            channel: options.paymentChannel || 'qris',
            status: 'idle',
            error: '',
        });

        try {
            const response = await createCustomerBooking(authToken, {
                branch_id: selectedBranch.id,
                service_ids: selectedServiceIds,
                booking_type: bookingType,
                staff_id: selectedStaffId || null,
                booking_date: bookingType === 'scheduled' ? bookingDate : null,
                start_time: bookingType === 'scheduled' ? startTime : null,
                payment_type: paymentType,
                coupon_code: options.couponCode || undefined,
                notes: notes || undefined,
            });
            const nextBooking = response.data;

            clearBookingDraft();
            clearPaymentDraft();
            setBookingSuccess(nextBooking);
            navigate('/booking/success', { replace: true });

            if (paymentType !== 'pay_at_salon' && nextBooking?.id) {
                void prepareBookingPayment(nextBooking.id, options.paymentChannel || 'qris', authToken);
            } else {
                void loadCustomerBookings(authToken, { quiet: true });
            }
        } catch (error) {
            setBookingError(error.message || 'Booking could not be created.');
        } finally {
            setBookingLoading(false);
        }
    }

    async function prepareBookingPayment(bookingId, channel = 'qris', token = authToken) {
        if (!bookingId || !token) return null;

        setPaymentSetup({ bookingId, channel, status: 'loading', error: '' });

        try {
            const paymentResponse = await createBookingPayment(token, bookingId, {
                payment_channel: channel,
            });
            const updatedBooking = paymentResponse.data || null;

            if (updatedBooking) {
                setBookingSuccess((current) => (
                    current && sameId(current.id, updatedBooking.id)
                        ? updatedBooking
                        : current
                ));
            }

            setPaymentSetup({ bookingId, channel, status: 'ready', error: '' });
            await loadCustomerBookings(token, { quiet: true });

            return updatedBooking;
        } catch (error) {
            setPaymentSetup({
                bookingId,
                channel,
                status: 'error',
                error: error.message || 'Payment instructions could not be generated.',
            });
            await loadCustomerBookings(token, { quiet: true });

            return null;
        }
    }

    async function loadCustomerBookings(token = authToken, options = {}) {
        if (!token) return;
        const { quiet = false } = options;

        if (!quiet) setBookingsLoading(true);

        try {
            const results = await getCustomerBookings(token, { per_page: 100 });
            setCustomerBookings(results);
        } catch {
            setCustomerBookings([]);
        } finally {
            if (!quiet) setBookingsLoading(false);
        }
    }

    async function openBookingsPanel() {
        if (!authUser || !authToken) {
            openAuthModal('login');
            return;
        }

        navigate('/my-bookings');
        await loadCustomerBookings(authToken);
    }

    async function handleCancelBooking(bookingId) {
        if (!authToken) return;

        try {
            await cancelCustomerBooking(authToken, bookingId);
            await loadCustomerBookings(authToken);
        } catch {
            // Keep current list if cancellation fails.
        }
    }

    async function refreshPaymentForBooking(bookingId) {
        if (!authToken || !bookingId) return null;

        const updatedBooking = await refreshBookingPaymentStatus(authToken, bookingId);

        if (updatedBooking) {
            setBookingSuccess((current) => (current && sameId(current.id, updatedBooking.id) ? updatedBooking : current));
            await loadCustomerBookings(authToken, { quiet: true });
        }

        return updatedBooking;
    }

    const selectedStaff = selectedStaffId
        ? (staffOptions.find((staff) => String(staff.id) === String(selectedStaffId)) || staffs.find((staff) => String(staff.id) === String(selectedStaffId)))
        : null;
    const selectedBranchServiceRoute = serviceRoute(selectedBranch?.id);
    const hasBookingBasics = Boolean(selectedBranch) && selectedServiceIds.length > 0;
    const hasSelectableStartTime = Boolean(startTime) && visibleSlots.some((slot) => slot.time === startTime && !slot.expired);
    const canContinueToPayment = hasBookingBasics && (bookingType === 'queue' || hasSelectableStartTime);
    const summary = (
        <BookingSummary
            branch={selectedBranch}
            services={selectedServices}
            bookingType={bookingType}
            selectedStaff={selectedStaff}
            bookingDate={bookingDate}
            startTime={startTime}
            paymentType={paymentType}
            totals={totals}
        />
    );
    const isAuthRoute = ['/signin', '/signup'].includes(location.pathname);

    return (
        <div className="customer-app">
            {!isAuthRoute && (
                <CustomerTopbar
                    authUser={authUser}
                    firstName={firstName}
                    onLogout={handleLogout}
                    onOpenBookings={openBookingsPanel}
                />
            )}

            <main>
                <Routes>
                    <Route
                        path="/signin"
                        element={(
                            <AuthPage
                                mode="login"
                                authUser={authUser}
                                authLoading={authLoading}
                                authError={authError}
                                authFieldError={authFieldError}
                                loginForm={loginForm}
                                setLoginForm={setLoginForm}
                                registerForm={registerForm}
                                setRegisterForm={setRegisterForm}
                                onLogin={handleLoginSubmit}
                                onRegister={handleRegisterSubmit}
                            />
                        )}
                    />

                    <Route
                        path="/signup"
                        element={(
                            <AuthPage
                                mode="register"
                                authUser={authUser}
                                authLoading={authLoading}
                                authError={authError}
                                authFieldError={authFieldError}
                                loginForm={loginForm}
                                setLoginForm={setLoginForm}
                                registerForm={registerForm}
                                setRegisterForm={setRegisterForm}
                                onLogin={handleLoginSubmit}
                                onRegister={handleRegisterSubmit}
                            />
                        )}
                    />

                    <Route
                        path="/"
                        element={(
                            <LandingContent
                                locations={locations}
                                branches={branches}
                                locationQuery={locationQuery}
                                setLocationQuery={setLocationQuery}
                                submitLocation={submitLocation}
                                useCurrentLocation={useCurrentLocation}
                                setSelectedLocation={setSelectedLocation}
                                loadBranches={loadBranches}
                                chooseBranch={chooseBranch}
                                isBooting={isBooting}
                                bookingDate={bookingDate}
                                setBookingDate={updateSearchBookingDate}
                                searchError={searchError}
                                currentCoords={searchCoordinates}
                                setCurrentCoords={setSearchCoordinates}
                            />
                        )}
                    />

                    <Route path="/promo" element={<MenuRoutePage page="promo" authUser={authUser} />} />
                    <Route path="/articles" element={<MenuRoutePage page="articles" authUser={authUser} />} />
                    <Route path="/business" element={<MenuRoutePage page="business" authUser={authUser} />} />

                    <Route
                        path={findServiceRoute}
                        element={(
                            <SearchResultsContent
                                branches={branches}
                                isLoading={isLoadingBranches}
                                selectedBranch={selectedBranch}
                                selectedLocation={selectedLocation}
                                chooseBranch={chooseBranch}
                                locations={locations}
                                locationQuery={locationQuery}
                                setLocationQuery={setLocationQuery}
                                submitLocation={submitLocation}
                                useCurrentLocation={useCurrentLocation}
                                setSelectedLocation={setSelectedLocation}
                                loadBranches={loadBranches}
                                isBooting={isBooting}
                                bookingDate={bookingDate}
                                setBookingDate={updateSearchBookingDate}
                                searchError={searchError}
                                currentCoords={searchCoordinates}
                                setCurrentCoords={setSearchCoordinates}
                            />
                        )}
                    />

                    <Route
                        path={`${findServiceRoute}/:branchId/services`}
                        element={(
                            <ServiceRoutePage
                                branch={selectedBranch}
                                services={filteredServices}
                                allServices={services}
                                categories={categories}
                                serviceCategory={serviceCategory}
                                setServiceCategory={setServiceCategory}
                                selectedServiceIds={selectedServiceIds}
                                toggleService={toggleService}
                                isLoadingBranch={isLoadingBranch}
                                refreshSelectedBranch={refreshSelectedBranch}
                                bookingDate={bookingDate}
                                setBookingDate={setBookingDate}
                                bookingType={bookingType}
                                setBookingType={setBookingType}
                                staffs={staffOptions}
                                selectedStaffId={selectedStaffId}
                                setSelectedStaffId={setSelectedStaffId}
                                selectedStaff={selectedStaff}
                                availabilityLoading={isAvailabilityResolving}
                                visibleSlots={visibleSlots}
                                startTime={startTime}
                                setStartTime={setStartTime}
                                queueEstimation={queueEstimation}
                                canContinueToPayment={canContinueToPayment}
                                totals={totals}
                            />
                        )}
                    />

                    <Route
                        path="/booking/mode"
                        element={<Navigate to={selectedBranchServiceRoute} replace />}
                    />

                    <Route
                        path="/booking/staff"
                        element={<Navigate to={selectedBranchServiceRoute} replace />}
                    />

                    <Route
                        path="/booking/schedule"
                        element={<Navigate to={canContinueToPayment ? '/booking/payment' : selectedBranchServiceRoute} replace />}
                    />

                    <Route
                        path="/booking/payment"
                        element={(
                            <BookingGuard condition={hasBookingBasics} redirectTo={selectedBranchServiceRoute}>
                                <ReviewStep
                                    authUser={authUser}
                                    branch={selectedBranch}
                                    services={selectedServices}
                                    bookingType={bookingType}
                                    staff={selectedStaff}
                                    bookingDate={bookingDate}
                                    startTime={startTime}
                                    totals={totals}
                                    paymentType={paymentType}
                                    setPaymentType={setPaymentType}
                                    notes={notes}
                                    setNotes={setNotes}
                                    bookingError={bookingError}
                                    bookingSuccess={bookingSuccess}
                                    bookingLoading={bookingLoading}
                                    submitBooking={submitBooking}
                                    openBookingsPanel={openBookingsPanel}
                                />
                            </BookingGuard>
                        )}
                    />

                    <Route
                        path="/booking/success"
                        element={(
                            <BookingSuccess
                                booking={bookingSuccess || customerBookings[0]}
                                loading={bookingsLoading}
                                openBookingsPanel={openBookingsPanel}
                                onPaymentRefresh={refreshPaymentForBooking}
                                paymentSetup={paymentSetup}
                                onPaymentSetupRetry={(booking, channel) => prepareBookingPayment(booking.id, channel, authToken)}
                            />
                        )}
                    />

                    <Route
                        path="/my-bookings"
                        element={(
                            <MyBookingsPage
                                authUser={authUser}
                                bookings={customerBookings}
                                loading={bookingsLoading}
                                onRefresh={() => loadCustomerBookings(authToken)}
                                onCancel={handleCancelBooking}
                                openAuthModal={openAuthModal}
                            />
                        )}
                    />

                    <Route
                        path="/my-bookings/:bookingId"
                        element={(
                            <MyBookingDetailPage
                                authUser={authUser}
                                authToken={authToken}
                                bookings={customerBookings}
                                loading={bookingsLoading}
                                onRefresh={() => loadCustomerBookings(authToken)}
                                onCancel={handleCancelBooking}
                                openAuthModal={openAuthModal}
                            />
                        )}
                    />

                    <Route path="*" element={<Navigate to="/" replace />} />
                </Routes>
            </main>

            {!isAuthRoute && <CustomerFooter branchCount={branches.length} />}

            {isAuthModalOpen && (
                <AuthModal
                    mode={authModalMode}
                    setMode={openAuthModal}
                    onClose={closeAuthModal}
                    authLoading={authLoading}
                    authError={authError}
                    authFieldError={authFieldError}
                    loginForm={loginForm}
                    setLoginForm={setLoginForm}
                    registerForm={registerForm}
                    setRegisterForm={setRegisterForm}
                    onLogin={handleLoginSubmit}
                    onRegister={handleRegisterSubmit}
                />
            )}
        </div>
    );
}

function BookingRouteLayout({ activeStep, children, summary, backTo, nextTo, nextDisabled = false, nextLabel = 'Next' }) {
    function handleNextClick(event) {
        if (nextDisabled) {
            event.preventDefault();
        }
    }

    return (
        <section className="booking-shell" id="booking">
            <div className="booking-main">
                <BookingProgress activeStep={activeStep} />
                {children}

                <div className="step-actions">
                    {backTo ? <Link className="btn btn-outline" to={backTo}>Back</Link> : <span />}
                    {nextTo && (
                        <Link
                            className={`btn btn-primary${nextDisabled ? ' disabled' : ''}`}
                            to={nextTo}
                            aria-disabled={nextDisabled}
                            onClick={handleNextClick}
                        >
                            {nextLabel}
                        </Link>
                    )}
                </div>
            </div>

            {summary}
        </section>
    );
}

function BookingGuard({ condition, redirectTo, children }) {
    if (!condition) {
        return <Navigate to={redirectTo || '/'} replace />;
    }

    return children;
}

function ServiceRoutePage({
    branch,
    services,
    allServices,
    categories,
    serviceCategory,
    setServiceCategory,
    selectedServiceIds,
    toggleService,
    isLoadingBranch,
    refreshSelectedBranch,
    bookingDate,
    setBookingDate,
    bookingType,
    setBookingType,
    staffs,
    selectedStaffId,
    setSelectedStaffId,
    selectedStaff,
    availabilityLoading,
    visibleSlots,
    startTime,
    setStartTime,
    queueEstimation,
    canContinueToPayment,
    totals,
}) {
    const { branchId } = useParams();
    const [loadFailed, setLoadFailed] = useState(false);
    const isCurrentBranch = Boolean(branchId && branch && sameId(branch.id, branchId));

    useEffect(() => {
        let cancelled = false;

        if (!branchId || isCurrentBranch) {
            return () => {
                cancelled = true;
            };
        }

        setLoadFailed(false);

        async function loadBranchDetail() {
            const detail = await refreshSelectedBranch(branchId);

            if (!cancelled && !detail) {
                setLoadFailed(true);
            }
        }

        void loadBranchDetail();

        return () => {
            cancelled = true;
        };
    }, [branchId, isCurrentBranch, refreshSelectedBranch]);

    if (!branchId) {
        return <Navigate to={findServiceRoute} replace />;
    }

    const isPreparingBranch = !isCurrentBranch && !loadFailed;

    if (isPreparingBranch) {
        return (
            <section className="route-state-shell">
                <GuideCard title="Loading salon details" text="Loading the latest services, staff, and branch schedule." />
            </section>
        );
    }

    if (loadFailed && !isCurrentBranch) {
        return (
            <section className="route-state-shell">
                <GuideCard title="Salon not found" text="This branch is unavailable or inactive." />
            </section>
        );
    }

    return (
        <BranchDetailContent
            branch={branch}
            services={services}
            allServices={allServices}
            categories={categories}
            serviceCategory={serviceCategory}
            setServiceCategory={setServiceCategory}
            selectedServiceIds={selectedServiceIds}
            toggleService={toggleService}
            bookingDate={bookingDate}
            setBookingDate={setBookingDate}
            bookingType={bookingType}
            setBookingType={setBookingType}
            staffs={staffs}
            selectedStaffId={selectedStaffId}
            setSelectedStaffId={setSelectedStaffId}
            selectedStaff={selectedStaff}
            availabilityLoading={availabilityLoading}
            visibleSlots={visibleSlots}
            startTime={startTime}
            setStartTime={setStartTime}
            queueEstimation={queueEstimation}
            canContinueToPayment={canContinueToPayment}
            totals={totals}
        />
    );
}

function BookingSuccess({ booking, loading = false, openBookingsPanel, onPaymentRefresh, paymentSetup = {}, onPaymentSetupRetry }) {
    const [checkingPayment, setCheckingPayment] = useState(false);

    async function checkPaymentStatus() {
        if (!booking?.id || !onPaymentRefresh) return;

        setCheckingPayment(true);

        try {
            await onPaymentRefresh(booking.id);
        } finally {
            setCheckingPayment(false);
        }
    }

    if (!booking) {
        return (
            <section className="booking-review-page booking-success-page">
                <section className="booking-review-hero booking-success-hero">
                    <div>
                        <nav className="booking-review-breadcrumb" aria-label="Booking breadcrumb">
                            <Link to="/"><Icon name="store" size={18} /> Home</Link>
                            <span />
                            <strong>Booking Status</strong>
                        </nav>
                        <h1>Booking Status</h1>
                        <p>{loading ? 'Loading your latest booking status.' : 'Complete payment first to create a new booking.'}</p>
                    </div>
                    <div className="booking-success-mark" aria-hidden="true">
                        <Icon name={loading ? 'clock' : 'calendar'} size={46} />
                    </div>
                </section>

                <section className="booking-success-empty booking-review-card">
                    <span><Icon name={loading ? 'clock' : 'calendar'} size={34} /></span>
                    <h2>{loading ? 'Loading booking status...' : 'No new booking yet'}</h2>
                    <p>{loading ? 'One moment, your booking data is being prepared.' : 'Complete review and payment first to see the booking status.'}</p>
                    {!loading && <Link className="btn btn-primary" to="/">Start Booking</Link>}
                </section>
            </section>
        );
    }

    const branch = booking.branch || {};
    const services = Array.isArray(booking.services) && booking.services.length
        ? booking.services
        : booking.service
            ? [booking.service]
            : [];
    const payment = booking.payment || {};
    const bookingType = booking.booking_type === 'queue' || booking.booking_type === 'walk_in'
        ? 'Queue'
        : 'Scheduled booking';
    const bookingTime = booking.start_time || booking.booking_time;
    const branchName = branch.branch_name || branch.name || 'Salon Branch';
    const providerProfile = booking.provider?.provider_profile
        || booking.provider?.providerProfile
        || branch.provider?.provider_profile
        || branch.provider?.providerProfile
        || {};
    const branchImage = resolveAssetUrl(
        branch.image_url
        || branch.image
        || branch.thumbnail
        || branch.photo
        || providerProfile.image_url
        || providerProfile.image
    ) || heroImage;
    const branchAddress = branch.address
        || [branch.city, branch.city_id, branch.state, branch.state_id, branch.country, branch.country_id].filter(Boolean).join(', ')
        || 'Salon address is not available';
    const staffName = booking.staff?.full_name || booking.staff?.name || booking.staff?.email || 'Any Available Staff';
    const payableAmount = Number(booking.total_price || booking.amount || payment.amount || 0);
    const serviceCount = services.length || 1;
    const statusKey = bookingDisplayStatus(booking);
    const status = statusLabel(statusKey);
    const paymentType = payment.payment_type || booking.payment_type || 'pay_at_salon';
    const isBookingCancelled = bookingIsCancelled(booking);
    const paymentAmount = isBookingCancelled ? 0 : Number(payment.amount ?? (booking.payment_status === 'paid' ? payableAmount : 0));
    const rawPaymentStatus = bookingEffectivePaymentStatus(booking);
    const paymentStatus = paymentStatusLabel(rawPaymentStatus);
    const hasSettledPayment = rawPaymentStatus === 'paid';
    const needsOnlinePayment = paymentType !== 'pay_at_salon' && !hasSettledPayment && !isBookingCancelled;
    const paymentSetupApplies = paymentSetup?.bookingId && sameId(paymentSetup.bookingId, booking.id);
    const paymentSetupStatus = paymentSetupApplies ? paymentSetup.status : 'idle';
    const paymentSetupError = paymentSetupApplies ? paymentSetup.error : '';
    const paymentSetupChannel = paymentSetupApplies ? paymentSetup.channel : payment.payment_channel;
    const hasPaymentInstruction = Boolean(payment.qr_url || payment.payment_code || payment.midtrans_order_id);
    const isPreparingPayment = needsOnlinePayment && paymentSetupStatus === 'loading' && !hasPaymentInstruction;
    const paymentSetupFailed = needsOnlinePayment && paymentSetupStatus === 'error' && !hasPaymentInstruction;
    const paymentFailed = needsOnlinePayment && rawPaymentStatus === 'failed';
    const heroTitle = isBookingCancelled
        ? 'Booking Cancelled'
        : needsOnlinePayment
        ? paymentSetupFailed ? 'Payment Setup Failed' : paymentFailed ? 'Payment Failed' : isPreparingPayment ? 'Preparing Payment' : 'Payment Pending'
        : 'Booking Successful';
    const heroDescription = isBookingCancelled
        ? 'This booking has been cancelled. No payment is required.'
        : needsOnlinePayment
        ? paymentSetupFailed
            ? 'Your booking has been created, but payment instructions could not be generated yet.'
            : isPreparingPayment
                ? 'Your booking has been created. Payment instructions are being prepared.'
                : paymentFailed
            ? 'Your booking has been created, but the payment could not be completed.'
            : 'Your booking has been created. Complete payment to secure your salon visit.'
        : 'Your booking has been created. Save this code for salon check-in.';
    const heroIcon = isBookingCancelled ? 'x' : needsOnlinePayment ? (paymentFailed ? 'info' : 'clock') : 'check';
    const heroState = isBookingCancelled ? 'is-failed' : needsOnlinePayment ? (paymentFailed || paymentSetupFailed ? 'is-failed' : 'is-pending') : 'is-success';
    const nextStepText = isBookingCancelled
            ? 'Payment flow stopped because the booking was cancelled.'
        : paymentType === 'pay_at_salon'
            ? 'Visit the salon on schedule and pay directly on site.'
        : paymentSetupFailed
            ? 'Payment instructions could not be generated. Try again from the payment card below.'
            : isPreparingPayment
                ? 'Preparing payment instructions. You can stay on this page while we connect to Midtrans.'
        : hasSettledPayment
            ? 'Payment has been received. Visit the salon at your scheduled booking time.'
            : 'Payment is being recorded. Keep your booking code for salon check-in.';

    return (
        <section className="booking-review-page booking-success-page">
            <section className="booking-review-hero booking-success-hero">
                <div>
                    <nav className="booking-review-breadcrumb" aria-label="Booking breadcrumb">
                        <Link to="/"><Icon name="store" size={18} /> Home</Link>
                        <span />
                        <strong>Booking Status</strong>
                    </nav>
                    <h1>{heroTitle}</h1>
                    <p>{heroDescription}</p>
                </div>
                <div className={`booking-success-mark ${heroState}`} aria-hidden="true">
                    <Icon name={heroIcon} size={46} />
                </div>
            </section>

            <div className="booking-review-layout booking-success-layout">
                <main className="booking-review-main">
                    <section className="booking-review-card booking-success-confirmation">
                        <header>
                            <h2><Icon name="shield" size={34} /> Booking Status</h2>
                            <span className={`booking-success-pill ${statusKey}`}>{status}</span>
                        </header>
                        <div className="booking-success-code">
                            <span>Booking Code</span>
                            <strong>{booking.booking_code || '-'}</strong>
                        </div>
                        <div className="booking-success-next">
                            <Icon name="info" size={22} />
                            <p>{nextStepText}</p>
                        </div>
                        <PaymentInstructionCard
                            payment={payment}
                            onCheckStatus={checkPaymentStatus}
                            checking={checkingPayment}
                            setupStatus={paymentSetupStatus}
                            setupError={paymentSetupError}
                            setupChannel={paymentSetupChannel}
                            onRetrySetup={onPaymentSetupRetry ? () => onPaymentSetupRetry(booking, paymentSetupChannel || 'qris') : null}
                            cancelled={isBookingCancelled}
                        />
                        <div className="booking-success-actions">
                            <button className="btn btn-primary" type="button" onClick={openBookingsPanel}>View My Bookings</button>
                            <Link className="btn btn-outline" to="/">Book Again</Link>
                        </div>
                    </section>

                    <section className="booking-review-card booking-success-details">
                        <header>
                            <h2><Icon name="store" size={34} /> Visit Details</h2>
                        </header>
                        <div className="booking-info-body">
                            <img src={branchImage} alt={branchName} />
                            <div>
                                <h3>{branchName}</h3>
                                <p><Icon name="pin" size={18} /> {branchAddress}</p>
                                <div className="booking-review-rating">
                                    {Array.from({ length: 5 }).map((_, index) => <Icon name="star" size={21} key={index} />)}
                                    <strong>{bookingType}</strong>
                                </div>
                            </div>
                        </div>

                        <div className="booking-info-stats">
                            <article>
                                <span>Date</span>
                                <strong>{formatDate(booking.booking_date)}</strong>
                                <small><Icon name="clock" size={16} /> {bookingTime ? formatTime(bookingTime) : 'Today'}</small>
                            </article>
                            <article>
                                <span>Staff</span>
                                <strong>{staffName}</strong>
                                <small><Icon name="users" size={16} /> {booking.queue_number ? `Queue #${booking.queue_number}` : 'Assigned'}</small>
                            </article>
                            <article>
                                <span>Services</span>
                                <strong>{serviceCount} selected</strong>
                                <small><Icon name="clock" size={16} /> {booking.total_duration || 0} minutes</small>
                            </article>
                        </div>
                    </section>

                    <section className="booking-review-card booking-success-services">
                        <header>
                            <h2><Icon name="list" size={34} /> Selected Services</h2>
                        </header>
                        <div>
                            {services.map((service) => (
                                <article key={service.id}>
                                    <span>{service.title || service.service_name || service.name || 'Service'}</span>
                                    <strong>{formatPrice(service.pivot?.price || service.price || 0)}</strong>
                                </article>
                            ))}
                            {services.length === 0 && (
                                <article>
                                    <span>Booked service</span>
                                    <strong>{formatPrice(payableAmount)}</strong>
                                </article>
                            )}
                        </div>
                    </section>
                </main>

                <aside className="booking-review-sidebar">
                    <section className="price-summary-card booking-success-summary">
                        <h2>Payment Summary</h2>
                        <div>
                            <span>Payment Type</span>
                            <strong>{paymentTypeLabel(paymentType)}</strong>
                        </div>
                        <div>
                            <span>Payment Status</span>
                            <strong>{paymentStatus}</strong>
                        </div>
                        <div>
                            <span>Due Now</span>
                            <strong>{formatPrice(paymentAmount)}</strong>
                        </div>
                        <footer>
                            <span>Total Booking</span>
                            <strong>{formatPrice(payableAmount)}</strong>
                        </footer>
                    </section>

                    <div className="payment-side-note">
                        <strong>{branchName}</strong>
                        <span>{formatDate(booking.booking_date)} - {bookingTime ? formatTime(bookingTime) : bookingType}</span>
                    </div>
                </aside>
            </div>
        </section>
    );
}

function PaymentInstructionCard({
    payment = {},
    onCheckStatus,
    checking = false,
    setupStatus = 'idle',
    setupError = '',
    setupChannel = 'qris',
    onRetrySetup = null,
    cancelled = false,
}) {
    const paymentType = payment.payment_type || 'pay_at_salon';
    const status = payment.status || 'pending';
    const isOnlinePayment = paymentType !== 'pay_at_salon';
    const isPaid = status === 'paid';

    if (!isOnlinePayment) return null;

    const hasInstruction = Boolean(payment.qr_url || payment.payment_code);
    const isSettingUp = setupStatus === 'loading' && !hasInstruction;
    const setupFailed = setupStatus === 'error' && !hasInstruction;
    const channelLabel = payment.payment_code_label
        || String(payment.payment_channel || setupChannel || 'Midtrans').replaceAll('_', ' ').toUpperCase();
    const expiryLabel = payment.expiry_time
        ? `${formatDate(payment.expiry_time)}, ${formatTime(payment.expiry_time)}`
        : 'Follow the expiry time shown by your payment app.';
    const statusClass = cancelled || setupFailed ? 'failed' : status;
    const statusText = cancelled
        ? 'Cancelled'
        : isSettingUp
        ? 'Preparing'
        : setupFailed
            ? 'Needs Retry'
            : paymentStatusLabel(status);
    const headingText = isPaid
        ? 'Payment Received'
        : cancelled
            ? 'Payment Cancelled'
        : isSettingUp
            ? 'Preparing instructions'
            : setupFailed
                ? 'Payment setup failed'
                : channelLabel;

    return (
        <section className="midtrans-instruction-card">
            <header>
                <div>
                    <span>Midtrans Payment</span>
                    <h3>{headingText}</h3>
                </div>
                <b className={`midtrans-status is-${statusClass}`}>{statusText}</b>
            </header>

            {cancelled ? (
                <div className="midtrans-empty-instruction is-error">
                    <Icon name="x" size={28} />
                    <p>This booking has been cancelled. No payment is required.</p>
                </div>
            ) : isSettingUp ? (
                <div className="midtrans-empty-instruction">
                    <Icon name="clock" size={28} />
                    <p>Preparing payment instructions with Midtrans. This can take a moment on sandbox.</p>
                </div>
            ) : setupFailed ? (
                <div className="midtrans-empty-instruction is-error">
                    <Icon name="info" size={28} />
                    <p>{setupError || 'Payment instructions could not be generated yet.'}</p>
                </div>
            ) : payment.qr_url ? (
                <div className="midtrans-live-qr">
                    <img src={payment.qr_url} alt="QRIS payment code" />
                    <div>
                        <strong>Scan QRIS</strong>
                        <p>Open your wallet or mobile banking app, scan this code, then confirm the exact amount.</p>
                        {payment.deeplink_url && <a href={payment.deeplink_url} target="_blank" rel="noreferrer">Open payment app</a>}
                    </div>
                </div>
            ) : payment.payment_code ? (
                <div className="midtrans-live-code">
                    {payment.biller_code && (
                        <article>
                            <span>Biller Code</span>
                            <strong>{payment.biller_code}</strong>
                        </article>
                    )}
                    <article>
                        <span>{payment.payment_code_label || 'Payment Code'}</span>
                        <strong>{payment.payment_code}</strong>
                    </article>
                </div>
            ) : (
                <div className="midtrans-empty-instruction">
                    <Icon name="info" size={28} />
                    <p>Payment instructions have not been generated yet.</p>
                </div>
            )}

            <footer>
                <span>{cancelled ? 'Payment flow stopped because the booking was cancelled.' : isSettingUp ? 'Connecting to Midtrans...' : setupFailed ? 'Retry payment setup to generate a new instruction.' : `Expires: ${expiryLabel}`}</span>
                {!cancelled && (setupFailed || (!hasInstruction && onRetrySetup && !isSettingUp)) ? (
                    <button type="button" onClick={onRetrySetup} disabled={isSettingUp}>
                        Retry Payment Setup
                    </button>
                ) : !cancelled && onCheckStatus && (
                    <button type="button" onClick={onCheckStatus} disabled={checking || isPaid}>
                        {checking ? 'Checking...' : isPaid ? 'Paid' : 'Check Status'}
                    </button>
                )}
            </footer>
        </section>
    );
}

function ModeStep({ bookingType, setBookingType, selectedServices }) {
    const hasScheduled = selectedServices.every((service) => service.isScheduledEnabled);
    const hasQueue = selectedServices.every((service) => service.isQueueEnabled);

    return (
        <section className="step-section">
            <div className="section-head">
                <div>
                    <span>Step 4</span>
                    <h2>Choose Booking Mode</h2>
                    <p>Use a fixed time for scheduled reservations, or join the queue for quick services.</p>
                </div>
            </div>

            <div className="option-grid">
                <button className={`option-card ${bookingType === 'scheduled' ? 'active' : ''}`} type="button" disabled={!hasScheduled} onClick={() => setBookingType('scheduled')}>
                    <Icon name="calendar" size={28} />
                    <strong>Fixed-Time Booking</strong>
                    <span>Choose an available date and slot.</span>
                </button>
                <button className={`option-card ${bookingType === 'queue' ? 'active' : ''}`} type="button" disabled={!hasQueue} onClick={() => setBookingType('queue')}>
                    <Icon name="users" size={28} />
                    <strong>Join Queue</strong>
                    <span>Check the wait estimate, then enter the queue.</span>
                </button>
            </div>
        </section>
    );
}

function StaffStep({ staffs, selectedStaffId, setSelectedStaffId, availabilityLoading, hasSelectedServices }) {
    const hasStaffOptions = staffs.length > 0;

    return (
        <section className="step-section">
            <div className="section-head">
                <div>
                    <span>Step 5</span>
                    <h2>Choose Staff</h2>
                    <p>Any Available Staff will pick the fastest staff member with all required service skills.</p>
                </div>
            </div>

            {availabilityLoading && !hasStaffOptions ? (
                <div className="empty-state">Checking eligible staff...</div>
            ) : hasSelectedServices && !hasStaffOptions ? (
                <div className="empty-state">No staff member has all skills for this service yet.</div>
            ) : (
                <div className="staff-grid">
                    {staffs.length > 0 && (
                        <button className={`staff-card ${selectedStaffId === '' ? 'active' : ''}`} type="button" onClick={() => setSelectedStaffId('')}>
                            <span className="avatar-icon"><Icon name="users" size={25} /></span>
                            <strong>Any Available Staff</strong>
                            <small>The system picks the fastest staff member</small>
                        </button>
                    )}

                    {staffs.map((staff) => (
                        <button className={`staff-card ${String(selectedStaffId) === String(staff.id) ? 'active' : ''}`} type="button" key={staff.id} onClick={() => setSelectedStaffId(String(staff.id))}>
                            {staff.image ? <img src={staff.image} alt={staff.name} /> : <span className={`avatar-icon ${staff.tone}`}>{staff.name.slice(0, 1)}</span>}
                            <strong>{staff.name}</strong>
                            <small>{staff.rating ? `${staff.rating} rating` : statusLabel(staff.status)}</small>
                        </button>
                    ))}
                </div>
            )}
        </section>
    );
}

function ScheduleStep({ bookingType, bookingDate, setBookingDate, startTime, setStartTime, visibleSlots, availabilityLoading, queueEstimation }) {
    const dateOptions = Array.from({ length: 7 }, (_, index) => {
        const date = new Date();
        date.setDate(date.getDate() + index);
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());

        const value = date.toISOString().slice(0, 10);

        return {
            value,
            day: new Intl.DateTimeFormat(customerDateLocale(), { day: '2-digit' }).format(date),
            weekday: new Intl.DateTimeFormat(customerDateLocale(), { weekday: 'short' }).format(date),
            month: new Intl.DateTimeFormat(customerDateLocale(), { month: 'short' }).format(date),
        };
    });

    return (
        <section className="step-section">
            <div className="section-head">
                <div>
                    <span>Step 6</span>
                    <h2>{bookingType === 'scheduled' ? 'Choose Schedule' : 'Queue Estimate'}</h2>
                    <p>{bookingType === 'scheduled' ? 'Slots are calculated from staff schedules and active bookings.' : 'Estimate is based on today active branch queue.'}</p>
                </div>
            </div>

            {bookingType === 'scheduled' ? (
                <>
                    <div className="schedule-date-row">
                        {dateOptions.map((date) => (
                            <button className={bookingDate === date.value ? 'active' : ''} type="button" key={date.value} onClick={() => setBookingDate(date.value)}>
                                <span>{date.weekday}</span>
                                <strong>{date.day}</strong>
                                <small>{date.month}</small>
                            </button>
                        ))}
                    </div>

                    <label className="date-field">
                        Date
                        <input type="date" min={todayInputValue()} value={bookingDate} onChange={(event) => setBookingDate(event.target.value)} />
                    </label>

                    {availabilityLoading && visibleSlots.length === 0 ? (
                        <div className="empty-state">Calculating slots...</div>
                    ) : visibleSlots.length === 0 ? (
                        <div className="empty-state">No slots are available for this selection yet.</div>
                    ) : (
                        <div className="slot-grid">
                            {visibleSlots.map((slot) => {
                                const expired = Boolean(slot.expired);
                                const active = startTime === slot.time && !expired;

                                return (
                                    <button
                                        className={active ? 'active' : ''}
                                        type="button"
                                        disabled={expired}
                                        key={`${slot.time}-${slot.staff_id}`}
                                        onClick={() => {
                                            if (!expired) setStartTime(slot.time);
                                        }}
                                    >
                                        <strong>{slot.time}</strong>
                                        <span>{expired ? 'Passed' : slot.staff_name}</span>
                                    </button>
                                );
                            })}
                        </div>
                    )}
                </>
            ) : (
                <div className="queue-estimation">
                    <Icon name="users" size={34} />
                    <div>
                        <span>Estimated wait</span>
                        <strong>{formatWaitLabel(queueEstimation?.label) || '10 - 25 minutes'}</strong>
                        <p>{queueEstimation?.waiting_count || 0} customers are waiting in the active queue.</p>
                    </div>
                </div>
            )}
        </section>
    );
}

function ReviewStep({ authUser, branch, services, bookingType, staff, bookingDate, startTime, totals, paymentType, setPaymentType, notes, setNotes, bookingError, bookingSuccess, bookingLoading, submitBooking, openBookingsPanel }) {
    const savedPaymentDraft = useMemo(readPaymentDraft, []);
    const [guestDetails, setGuestDetails] = useState(() => savedPaymentDraft.guestDetails);
    const [requests, setRequests] = useState(() => savedPaymentDraft.requests);
    const [couponCode, setCouponCode] = useState(() => savedPaymentDraft.couponCode);
    const [paymentChannel, setPaymentChannel] = useState(() => savedPaymentDraft.paymentChannel);
    const [appliedCoupon, setAppliedCoupon] = useState(null);
    const [couponLoading, setCouponLoading] = useState(false);
    const [couponFeedback, setCouponFeedback] = useState({ type: '', text: '' });
    const subtotal = Number(totals.price || 0);
    const serviceIds = services.map((service) => service.id).filter(Boolean);
    const serviceKey = serviceIds.join(',');
    const discount = Number(appliedCoupon?.discount_amount || 0);
    const afterDiscount = Math.max(0, subtotal - discount);
    const taxes = Math.round(afterDiscount * 0.05);
    const payable = afterDiscount + taxes;
    const dueNow = paymentType === 'pay_at_salon'
        ? 0
        : paymentType === 'dp'
            ? Math.ceil(payable * 0.3)
            : payable;
    const scheduleLabel = bookingType === 'scheduled'
        ? `${formatDate(bookingDate)}, ${startTime || '-'}`
        : 'Today queue';
    const primaryService = services[0];
    const requestOptions = ['Area near the window', 'Arriving a little late', 'Need a consultation first', 'Quiet room', 'Female staff', 'No scented products'];
    const appliedCouponCode = appliedCoupon?.coupon?.code || '';
    const isAuthenticated = Boolean(authUser);
    const selectedPaymentChannel = midtransPaymentChannels.find((channel) => channel.id === paymentChannel) || midtransPaymentChannels[0];
    const onlinePaymentSelected = paymentType !== 'pay_at_salon';

    useEffect(() => {
        if (bookingSuccess) return;

        writePaymentDraft({
            guestDetails,
            requests,
            couponCode,
            paymentChannel,
        });
    }, [bookingSuccess, couponCode, guestDetails, paymentChannel, requests]);

    useEffect(() => {
        setAppliedCoupon(null);
        setCouponFeedback({ type: '', text: '' });
    }, [serviceKey, subtotal]);

    useEffect(() => {
        if (!authUser) return;

        const profile = authUser.customer_profile || authUser.customerProfile || {};
        const fullName = String(authUser.name || profile.name || '').trim();
        const [givenName = '', ...familyNames] = fullName.split(/\s+/).filter(Boolean);
        const phone = profile.phone_number
            || profile.phone
            || profile.whatsapp
            || authUser.phone_number
            || authUser.phone
            || '';

        setGuestDetails((current) => ({
            ...current,
            firstName: current.firstName || givenName,
            lastName: current.lastName || familyNames.join(' '),
            email: current.email || authUser.email || profile.email || '',
            phone: current.phone || phone,
        }));
    }, [authUser]);

    function updateGuestField(field, value) {
        setGuestDetails((current) => ({ ...current, [field]: value }));
    }

    function toggleRequest(request) {
        setRequests((current) => (
            current.includes(request)
                ? current.filter((item) => item !== request)
                : [...current, request]
        ));
    }

    async function handleApplyCoupon(event) {
        event.preventDefault();

        const code = couponCode.trim();
        if (!code) {
            setAppliedCoupon(null);
            setCouponFeedback({ type: 'error', text: 'Enter a voucher code first.' });
            return;
        }

        setCouponLoading(true);
        setCouponFeedback({ type: '', text: '' });

        try {
            const result = await validateCoupon({
                coupon_code: code,
                service_ids: serviceIds,
            });

            setAppliedCoupon(result);
            setCouponCode(result.coupon?.code || code.toUpperCase());
            setCouponFeedback({ type: 'success', text: 'Voucher applied successfully.' });
        } catch (error) {
            const message = error.errors?.coupon_code?.[0] || error.message || 'Voucher cannot be used.';
            setAppliedCoupon(null);
            setCouponFeedback({ type: 'error', text: message });
        } finally {
            setCouponLoading(false);
        }
    }

    function removeCoupon() {
        setAppliedCoupon(null);
        setCouponFeedback({ type: '', text: '' });
    }

    if (bookingSuccess) return <Navigate to="/booking/success" replace />;

    return (
        <section className="booking-review-page">
            <section className="booking-review-hero">
                <div>
                    <nav className="booking-review-breadcrumb" aria-label="Booking breadcrumb">
                        <Link to="/"><Icon name="store" size={18} /> Home</Link>
                        <span />
                        <Link to={serviceRoute(branch?.id)}>Salon detail</Link>
                        <span />
                        <strong>Booking</strong>
                    </nav>
                    <h1>Review your Booking</h1>
                </div>

                <div className="booking-phone-preview" aria-hidden="true">
                    <div>
                        <img src={branch?.image || heroImage} alt="" />
                        <span>BOOK</span>
                        <i>{formatPrice(dueNow || payable)}</i>
                    </div>
                </div>
            </section>

            {bookingError && <div className="booking-review-alert">{bookingError}</div>}

            <div className="booking-review-layout">
                <main className="booking-review-main">
                    <section className="booking-review-card booking-info-card">
                        <header>
                            <h2><Icon name="store" size={42} /> Salon Information</h2>
                        </header>

                        <div className="booking-info-body">
                            <img src={branch?.image || heroImage} alt={branch?.name || 'Salon'} />
                            <div>
                                <h3>{branch?.name || 'Salon Branch'}</h3>
                                <p><Icon name="pin" size={18} /> {branch?.address || branch?.locationLabel || 'Salon address is not available'}</p>
                                <div className="booking-review-rating">
                                    {Array.from({ length: 5 }).map((_, index) => <Icon name="star" size={21} key={index} />)}
                                    <strong>{Number(branch?.rating || 4.8).toFixed(1)}/5.0</strong>
                                </div>
                            </div>
                        </div>

                        <div className="booking-info-stats">
                            <article>
                                <span>{bookingType === 'scheduled' ? 'Booking date' : 'Queue date'}</span>
                                <strong>{formatDate(bookingDate)}</strong>
                                <small><Icon name="clock" size={16} /> {bookingType === 'scheduled' ? startTime : 'Today'}</small>
                            </article>
                            <article>
                                <span>Staff</span>
                                <strong>{staff?.name || 'Any Staff'}</strong>
                                <small><Icon name="users" size={16} /> {bookingType === 'scheduled' ? 'Scheduled service' : 'Queue service'}</small>
                            </article>
                            <article>
                                <span>Services</span>
                                <strong>{services.length} selected</strong>
                                <small><Icon name="clock" size={16} /> {totals.duration || 0} minutes</small>
                            </article>
                        </div>
                    </section>

                    <section className="booking-review-card service-included-card">
                        <header>
                            <h2>{primaryService?.title || 'Selected Service'}</h2>
                            <Link to={serviceRoute(branch?.id)}>View Cancellation Policy</Link>
                        </header>
                        <div className="booking-included-body">
                            <h3>Price Included</h3>
                            {services.map((service) => (
                                <p key={service.id}>
                                    <Icon name="check" size={18} />
                                    <span>{service.title} - {formatPrice(service.price)}.</span>
                                </p>
                            ))}
                            <p><Icon name="check" size={18} /><span>Staff and schedule follow your booking choices.</span></p>
                            <p><Icon name="check" size={18} /><span>Schedule changes follow salon policy.</span></p>
                        </div>
                    </section>

                    <section className="booking-review-card guest-details-card">
                        <header>
                            <h2><Icon name="users" size={38} /> Guest Details</h2>
                        </header>
                        <div className="guest-form-body">
                            <div className="guest-strip">Main Guest</div>
                            <div className="guest-field-grid">
                                <label>
                                    Title
                                    <select value={guestDetails.title} onChange={(event) => updateGuestField('title', event.target.value)}>
                                        <option value="">Title</option>
                                        <option value="mr">Mr</option>
                                        <option value="ms">Ms</option>
                                        <option value="mrs">Mrs</option>
                                    </select>
                                </label>
                                <label>
                                    First Name
                                    <input value={guestDetails.firstName} onChange={(event) => updateGuestField('firstName', event.target.value)} placeholder="Enter your first name" />
                                </label>
                                <label>
                                    Last Name
                                    <input value={guestDetails.lastName} onChange={(event) => updateGuestField('lastName', event.target.value)} placeholder="Enter your last name" />
                                </label>
                            </div>

                            <button className="add-guest-button" type="button">+ Add New Guest</button>

                            <div className="guest-contact-grid">
                                <label>
                                    Email id
                                    <input type="email" value={guestDetails.email} onChange={(event) => updateGuestField('email', event.target.value)} placeholder="Enter your email" />
                                    <small>Booking voucher will be sent to this email ID</small>
                                </label>
                                <label>
                                    Mobile number
                                    <input value={guestDetails.phone} onChange={(event) => updateGuestField('phone', event.target.value)} placeholder="Enter your mobile number" />
                                </label>
                            </div>

                            {!isAuthenticated && (
                                <Link className="guest-login-banner" to="/signin">
                                    <strong>Login</strong> to prefill all details and get access to secret deals
                                </Link>
                            )}

                            <section className="special-request-box">
                                <h3>Special request</h3>
                                <div>
                                    {requestOptions.map((request) => (
                                        <label key={request}>
                                            <input
                                                type="checkbox"
                                                checked={requests.includes(request)}
                                                onChange={() => toggleRequest(request)}
                                            />
                                            <span>{request}</span>
                                        </label>
                                    ))}
                                </div>
                            </section>
                        </div>
                    </section>

                    <section className="booking-review-card payment-options-card">
                        <header>
                            <h2><Icon name="card" size={38} /> Payment Options</h2>
                        </header>

                        <div className="payment-options-body">
                            {!isAuthenticated && (
                                <div className="payment-login-discount">
                                    <Icon name="card" size={54} />
                                    <div>
                                        <strong>Get Additional Discount</strong>
                                        <span>Login to access saved payments and discounts!</span>
                                    </div>
                                    <Link to="/signin">Login now</Link>
                                </div>
                            )}

                            <div className="payment-method-panel midtrans-payment-panel">
                                <div className="payment-type-grid">
                                    <button
                                        className={paymentType === 'full_payment' ? 'active' : ''}
                                        type="button"
                                        onClick={() => setPaymentType('full_payment')}
                                    >
                                        <span><Icon name="shield" size={20} /> Full Payment</span>
                                        <strong>{formatPrice(payable)}</strong>
                                    </button>
                                    <button
                                        className={paymentType === 'dp' ? 'active' : ''}
                                        type="button"
                                        onClick={() => setPaymentType('dp')}
                                    >
                                        <span><Icon name="card" size={20} /> Down Payment</span>
                                        <strong>{formatPrice(Math.ceil(payable * 0.3))}</strong>
                                    </button>
                                    <button
                                        className={paymentType === 'pay_at_salon' ? 'active' : ''}
                                        type="button"
                                        onClick={() => setPaymentType('pay_at_salon')}
                                    >
                                        <span><Icon name="money" size={20} /> Pay at Salon</span>
                                        <strong>{formatPrice(0)}</strong>
                                    </button>
                                </div>

                                {onlinePaymentSelected ? (
                                    <>
                                        <div className="midtrans-channel-head">
                                            <div>
                                                <strong>Online Payment</strong>
                                                <span>Choose QRIS or virtual account. Payment details appear after checkout.</span>
                                            </div>
                                            <b>Secure</b>
                                        </div>

                                        <div className="midtrans-channel-grid">
                                            {midtransPaymentChannels.map((channel) => (
                                                <button
                                                    className={paymentChannel === channel.id ? 'active' : ''}
                                                    type="button"
                                                    onClick={() => setPaymentChannel(channel.id)}
                                                    key={channel.id}
                                                >
                                                    <strong>{channel.label}</strong>
                                                    <span>{channel.detail}</span>
                                                </button>
                                            ))}
                                        </div>

                                        <div className="midtrans-preview-card">
                                            {paymentChannel === 'qris' ? (
                                                <div className="midtrans-qr-placeholder">
                                                    <Icon name="qr" size={46} />
                                                    <span>QRIS will appear after checkout</span>
                                                </div>
                                            ) : (
                                                <div className="midtrans-code-placeholder">
                                                    <span>Payment code will appear here</span>
                                                    <strong>{selectedPaymentChannel.label}</strong>
                                                </div>
                                            )}
                                            <div>
                                                <h3>{selectedPaymentChannel.label}</h3>
                                                <p>{selectedPaymentChannel.detail}</p>
                                                <small>The booking status updates after the payment is confirmed.</small>
                                            </div>
                                        </div>
                                    </>
                                ) : (
                                    <div className="midtrans-preview-card pay-at-salon-preview">
                                        <span><Icon name="store" size={38} /></span>
                                        <div>
                                            <h3>Pay at Salon</h3>
                                            <p>No online payment is created. Pay directly when you arrive at the branch.</p>
                                            <small>Booking is still saved so the salon can prepare your slot.</small>
                                        </div>
                                    </div>
                                )}

                                <div className="payment-due-row">
                                    <strong>{formatPrice(dueNow)}</strong>
                                    <span>Due now</span>
                                    <button type="button" disabled={bookingLoading} onClick={() => submitBooking({ couponCode: appliedCouponCode, paymentChannel })}>
                                        {bookingLoading ? 'Processing...' : paymentType === 'pay_at_salon' ? 'Book Now' : 'Generate Payment'}
                                    </button>
                                </div>
                            </div>

                            <label className="payment-note-field">
                                Note for the salon
                                <textarea value={notes} onChange={(event) => setNotes(event.target.value)} placeholder="Write an additional note, optional" />
                            </label>

                            <p className="payment-terms">By processing, You accept Booking <Link to="/business">Terms of Services</Link> and <Link to="/promo">Policy</Link></p>
                        </div>
                    </section>
                </main>

                <aside className="booking-review-sidebar">
                    <section className="price-summary-card">
                        <h2>Price Summary</h2>
                        <div>
                            <span>Service Charges</span>
                            <strong>{formatPrice(subtotal)}</strong>
                        </div>
                        <div>
                            <span>Total Discount {appliedCouponCode && <b>{appliedCouponCode}</b>}</span>
                            <strong className="discount">{discount > 0 ? `-${formatPrice(discount)}` : formatPrice(0)}</strong>
                        </div>
                        <div>
                            <span>Price after discount</span>
                            <strong>{formatPrice(afterDiscount)}</strong>
                        </div>
                        <div>
                            <span>Fee & Tax <b>5%</b></span>
                            <strong>{formatPrice(taxes)}</strong>
                        </div>
                        <footer>
                            <span>Payable Now</span>
                            <strong>{formatPrice(payable)}</strong>
                        </footer>
                    </section>

                    <section className="offer-discount-card">
                        <h2>Offer & Discount</h2>
                        {appliedCoupon ? (
                            <article>
                                <b />
                                <div>
                                    <strong>{appliedCouponCode}</strong>
                                    <span>Voucher active. You saved {formatPrice(discount)} on this booking.</span>
                                    <small>-{formatPrice(discount)}</small>
                                </div>
                                <button type="button" onClick={removeCoupon}>Remove</button>
                            </article>
                        ) : (
                            <p className="coupon-empty-note">Enter a voucher code to check available discounts.</p>
                        )}
                        <form onSubmit={handleApplyCoupon}>
                            <input
                                value={couponCode}
                                onChange={(event) => setCouponCode(event.target.value)}
                                placeholder="Coupon code"
                                disabled={couponLoading}
                            />
                            <button type="submit" disabled={couponLoading}>{couponLoading ? 'Checking' : 'Apply'}</button>
                        </form>
                        {couponFeedback.text && <p className={`coupon-feedback ${couponFeedback.type}`}>{couponFeedback.text}</p>}
                    </section>

                    {!isAuthenticated && (
                        <section className="booking-login-benefits">
                            <h2>Why Sign up or Log in</h2>
                            <p><Icon name="check" size={20} /> Get Access to Secret Deal</p>
                            <p><Icon name="check" size={20} /> Book Faster</p>
                            <p><Icon name="check" size={20} /> Manage Your Booking</p>
                        </section>
                    )}

                    <div className="payment-side-note">
                        <strong>{branch?.name || 'Salon Branch'}</strong>
                        <span>{scheduleLabel}</span>
                    </div>
                </aside>
            </div>
        </section>
    );
}

function BookingSummary({ branch, services, bookingType, selectedStaff, bookingDate, startTime, paymentType, totals }) {
    return (
        <aside className="booking-summary-panel">
            <h3>Summary</h3>
            {branch ? (
                <div className="summary-branch">
                    <img src={branch.image} alt={branch.name} />
                    <div>
                        <strong>{branch.name}</strong>
                        <span>{branch.locationLabel}</span>
                    </div>
                </div>
            ) : (
                <p className="summary-muted">Choose a location and salon to start.</p>
            )}

            <div className="summary-list">
                {services.map((service) => (
                    <div key={service.id}>
                        <span>{service.title}</span>
                        <strong>{formatPrice(service.price)}</strong>
                    </div>
                ))}
            </div>

            <div className="summary-total">
                <div><span>Duration</span><strong>{totals.duration} minutes</strong></div>
                <div><span>Total</span><strong>{formatPrice(totals.price)}</strong></div>
            </div>

            <div className="summary-tags">
                <span>{bookingType === 'scheduled' ? 'Fixed time' : 'Queue'}</span>
                <span>{selectedStaff?.name || 'Any staff'}</span>
                <span>{bookingType === 'scheduled' ? `${bookingDate} ${startTime || ''}` : 'Today'}</span>
                <span>{paymentType.replaceAll('_', ' ')}</span>
            </div>
        </aside>
    );
}

function GuideCard({ title, text }) {
    return (
        <section className="guide-card">
            <Icon name="pin" size={34} />
            <h2>{title}</h2>
            <p>{text}</p>
        </section>
    );
}

function MyBookingsAuthPrompt({ openAuthModal }) {
    return (
        <section className="booking-review-page my-bookings-page">
            <section className="booking-review-hero my-bookings-hero">
                <div>
                    <nav className="booking-review-breadcrumb" aria-label="My bookings breadcrumb">
                        <Link to="/"><Icon name="store" size={18} /> Home</Link>
                        <span />
                        <strong>My Bookings</strong>
                    </nav>
                    <h1>My Bookings</h1>
                    <p>Sign in first to view your booking list and details.</p>
                </div>
                <div className="my-bookings-mark" aria-hidden="true"><Icon name="bookmark" size={42} /></div>
            </section>

            <section className="booking-review-card my-bookings-empty">
                <span><Icon name="users" size={34} /></span>
                <h2>Login required</h2>
                <p>After login, all customer bookings will appear here.</p>
                <button className="btn btn-primary" type="button" onClick={() => openAuthModal('login')}>Login Customer</button>
            </section>
        </section>
    );
}

function MyBookingCard({ booking, onCancel }) {
    const branch = bookingBranchMeta(booking);
    const services = bookingServiceItems(booking);
    const payment = booking.payment || {};
    const paymentType = payment.payment_type || booking.payment_type || 'pay_at_salon';
    const amount = Number(booking.total_price || booking.amount || payment.amount || 0);
    const displayStatus = bookingDisplayStatus(booking);

    return (
        <article className="my-booking-card">
            <Link className="my-booking-card-main" to={`/my-bookings/${booking.id}`}>
                <img src={branch.image} alt={branch.name} />
                <div>
                    <div className="my-booking-card-top">
                        <strong>{bookingServicesText(booking)}</strong>
                        <span className={`booking-status ${displayStatus}`}>{statusLabel(displayStatus)}</span>
                    </div>
                    <p>{branch.name}</p>
                    <div className="my-booking-meta">
                        <span><Icon name="calendar" size={16} /> {bookingDateTimeLabel(booking)}</span>
                        <span><Icon name="card" size={16} /> {paymentTypeLabel(paymentType)}</span>
                        <span><Icon name="money" size={16} /> {formatPrice(amount)}</span>
                    </div>
                    <small>{booking.booking_code || '-'} - {services.length || 1} services</small>
                </div>
            </Link>

            <div className="my-booking-card-actions">
                <Link to={`/my-bookings/${booking.id}`}>Details</Link>
                {canCancelBooking(booking) && (
                    <button type="button" onClick={() => onCancel(booking.id)}>Cancel</button>
                )}
            </div>
        </article>
    );
}

function MyBookingsPage({ authUser, bookings, loading, onRefresh, onCancel, openAuthModal }) {
    useEffect(() => {
        if (authUser) void onRefresh?.();
    }, [authUser?.id]);

    if (!authUser) {
        return <MyBookingsAuthPrompt openAuthModal={openAuthModal} />;
    }

    const activeCount = bookings.filter((booking) => canCancelBooking(booking)).length;
    const completedCount = bookings.filter((booking) => ['completed', 'order_completed'].includes(booking.status)).length;

    return (
        <section className="booking-review-page my-bookings-page">
            <section className="booking-review-hero my-bookings-hero">
                <div>
                    <nav className="booking-review-breadcrumb" aria-label="My bookings breadcrumb">
                        <Link to="/"><Icon name="store" size={18} /> Home</Link>
                        <span />
                        <strong>My Bookings</strong>
                    </nav>
                    <h1>My Bookings</h1>
                    <p>Manage active bookings, view visit details, and check your payment status.</p>
                </div>
                <div className="my-bookings-mark" aria-hidden="true"><Icon name="bookmark" size={42} /></div>
            </section>

            <div className="my-bookings-stats">
                <article>
                    <span>Total Booking</span>
                    <strong>{bookings.length}</strong>
                </article>
                <article>
                    <span>Active</span>
                    <strong>{activeCount}</strong>
                </article>
                <article>
                    <span>Completed</span>
                    <strong>{completedCount}</strong>
                </article>
                <button type="button" onClick={onRefresh} disabled={loading}>
                    <Icon name="clock" size={17} /> {loading ? 'Loading...' : 'Refresh'}
                </button>
            </div>

            {loading && bookings.length === 0 ? (
                <section className="booking-review-card my-bookings-empty">
                    <span><Icon name="clock" size={34} /></span>
                    <h2>Loading bookings...</h2>
                    <p>Customer bookings are being prepared.</p>
                </section>
            ) : bookings.length === 0 ? (
                <section className="booking-review-card my-bookings-empty">
                    <span><Icon name="calendar" size={34} /></span>
                    <h2>No bookings yet</h2>
                    <p>Successful bookings will appear here.</p>
                    <Link className="btn btn-primary" to="/">Start Booking</Link>
                </section>
            ) : (
                <div className="my-bookings-list">
                    {bookings.map((booking) => (
                        <MyBookingCard key={booking.id} booking={booking} onCancel={onCancel} />
                    ))}
                </div>
            )}
        </section>
    );
}

function MyBookingDetailPage({ authUser, authToken, bookings, loading, onRefresh, onCancel, openAuthModal }) {
    const { bookingId } = useParams();
    const bookingFromList = useMemo(
        () => bookings.find((booking) => sameId(booking.id, bookingId) || sameId(booking.booking_code, bookingId)),
        [bookings, bookingId]
    );
    const [bookingDetail, setBookingDetail] = useState(null);
    const [detailLoading, setDetailLoading] = useState(false);
    const [detailError, setDetailError] = useState('');
    const [cancelLoading, setCancelLoading] = useState(false);
    const [checkingPayment, setCheckingPayment] = useState(false);
    const booking = bookingDetail || bookingFromList;

    useEffect(() => {
        if (!authUser || !authToken || !bookingId) return undefined;

        let mounted = true;

        async function loadDetail() {
            setDetailError('');

            if (bookingFromList) {
                setBookingDetail(bookingFromList);
                return;
            }

            setDetailLoading(true);

            try {
                const result = await getCustomerBooking(authToken, bookingId);
                if (mounted) setBookingDetail(result);
            } catch {
                if (mounted) setDetailError('Booking was not found or could not be loaded.');
            } finally {
                if (mounted) setDetailLoading(false);
            }
        }

        void loadDetail();

        return () => {
            mounted = false;
        };
    }, [authUser?.id, authToken, bookingId, bookingFromList]);

    if (!authUser) {
        return <MyBookingsAuthPrompt openAuthModal={openAuthModal} />;
    }

    async function cancelCurrentBooking() {
        if (!booking || cancelLoading) return;

        setCancelLoading(true);

        try {
            await onCancel(booking.id);
            setBookingDetail((current) => {
                const source = current || booking;

                return {
                    ...source,
                    status: 'cancelled',
                    payment_status: source.payment_status === 'paid' ? source.payment_status : 'failed',
                    payment: source.payment
                        ? {
                            ...source.payment,
                            status: source.payment.status === 'paid' ? source.payment.status : 'failed',
                        }
                        : source.payment,
                };
            });
            await onRefresh?.();
        } finally {
            setCancelLoading(false);
        }
    }

    async function checkCurrentPaymentStatus() {
        if (!booking || !authToken || checkingPayment) return;

        setCheckingPayment(true);

        try {
            const result = await refreshBookingPaymentStatus(authToken, booking.id);

            if (result) {
                setBookingDetail(result);
                await onRefresh?.();
            }
        } finally {
            setCheckingPayment(false);
        }
    }

    if ((loading || detailLoading) && !booking) {
        return (
            <section className="booking-review-page my-bookings-page">
                <section className="booking-review-card my-bookings-empty">
                    <span><Icon name="clock" size={34} /></span>
                    <h2>Loading booking details...</h2>
                    <p>One moment, booking data is being loaded.</p>
                </section>
            </section>
        );
    }

    if (!booking || detailError) {
        return (
            <section className="booking-review-page my-bookings-page">
                <section className="booking-review-card my-bookings-empty">
                    <span><Icon name="info" size={34} /></span>
                    <h2>Booking not found</h2>
                    <p>{detailError || 'This booking is not in your customer account.'}</p>
                    <Link className="btn btn-primary" to="/my-bookings">Back to My Bookings</Link>
                </section>
            </section>
        );
    }

    const branch = bookingBranchMeta(booking);
    const services = bookingServiceItems(booking);
    const payment = booking.payment || {};
    const paymentType = payment.payment_type || booking.payment_type || 'pay_at_salon';
    const isBookingCancelled = bookingIsCancelled(booking);
    const paymentStatus = bookingEffectivePaymentStatus(booking);
    const paymentAmount = isBookingCancelled ? 0 : Number(payment.amount ?? 0);
    const totalAmount = Number(booking.total_price || booking.amount || payment.amount || 0);
    const staffName = booking.staff?.full_name || booking.staff?.name || booking.staff?.email || 'Any Available Staff';
    const bookingTime = booking.start_time || booking.booking_time;
    const displayStatus = bookingDisplayStatus(booking);
    const heroVisual = bookingHeroVisual(booking);

    return (
        <section className="booking-review-page my-bookings-page my-booking-detail-page">
            <section className="booking-review-hero my-bookings-hero">
                <div>
                    <nav className="booking-review-breadcrumb" aria-label="Booking detail breadcrumb">
                        <Link to="/"><Icon name="store" size={18} /> Home</Link>
                        <span />
                        <Link to="/my-bookings">My Bookings</Link>
                        <span />
                        <strong>{booking.booking_code || 'Detail'}</strong>
                    </nav>
                    <h1>Booking Detail</h1>
                    <p>View this booking visit information, services, staff, and payment status.</p>
                </div>
                <div className={`my-bookings-mark ${heroVisual.state}`} aria-hidden="true">
                    <Icon name={heroVisual.icon} size={42} />
                </div>
            </section>

            <div className="booking-review-layout my-booking-detail-layout">
                <main className="booking-review-main">
                    <section className="booking-review-card booking-success-confirmation">
                        <header>
                            <h2><Icon name="shield" size={34} /> {booking.booking_code || 'Booking'}</h2>
                            <span className={`booking-status ${displayStatus}`}>{statusLabel(displayStatus)}</span>
                        </header>
                        <div className="booking-success-code">
                            <span>Booking Code</span>
                            <strong>{booking.booking_code || '-'}</strong>
                        </div>
                        <PaymentInstructionCard
                            payment={payment}
                            onCheckStatus={checkCurrentPaymentStatus}
                            checking={checkingPayment}
                            cancelled={isBookingCancelled}
                        />
                        <div className="booking-success-actions">
                            <Link className="btn btn-outline" to="/my-bookings">Back</Link>
                            {canCancelBooking(booking) && (
                                <button className="btn btn-primary danger" type="button" onClick={cancelCurrentBooking} disabled={cancelLoading}>
                                    {cancelLoading ? 'Cancelling...' : 'Cancel Booking'}
                                </button>
                            )}
                        </div>
                    </section>

                    <section className="booking-review-card booking-success-details">
                        <header>
                            <h2><Icon name="store" size={34} /> Visit Details</h2>
                        </header>
                        <div className="booking-info-body">
                            <img src={branch.image} alt={branch.name} />
                            <div>
                                <h3>{branch.name}</h3>
                                <p><Icon name="pin" size={18} /> {branch.address}</p>
                                <div className="booking-review-rating">
                                    {Array.from({ length: 5 }).map((_, index) => <Icon name="star" size={21} key={index} />)}
                                    <strong>{booking.booking_type === 'queue' ? 'Queue' : 'Scheduled booking'}</strong>
                                </div>
                            </div>
                        </div>

                        <div className="booking-info-stats">
                            <article>
                                <span>Date</span>
                                <strong>{formatDate(booking.booking_date)}</strong>
                                <small><Icon name="clock" size={16} /> {bookingTime ? formatTime(bookingTime) : 'Today'}</small>
                            </article>
                            <article>
                                <span>Staff</span>
                                <strong>{staffName}</strong>
                                <small><Icon name="users" size={16} /> {booking.queue_number ? `Queue #${booking.queue_number}` : 'Assigned'}</small>
                            </article>
                            <article>
                                <span>Duration</span>
                                <strong>{booking.total_duration || 0} minutes</strong>
                                <small><Icon name="list" size={16} /> {services.length || 1} services</small>
                            </article>
                        </div>
                    </section>

                    <section className="booking-review-card booking-success-services">
                        <header>
                            <h2><Icon name="list" size={34} /> Selected Services</h2>
                        </header>
                        <div>
                            {services.map((service) => (
                                <article key={service.id}>
                                    <span>{service.title || service.service_name || service.name || 'Service'}</span>
                                    <strong>{formatPrice(service.pivot?.price || service.price || 0)}</strong>
                                </article>
                            ))}
                            {services.length === 0 && (
                                <article>
                                    <span>Booked service</span>
                                    <strong>{formatPrice(totalAmount)}</strong>
                                </article>
                            )}
                        </div>
                    </section>
                </main>

                <aside className="booking-review-sidebar">
                    <section className="price-summary-card booking-success-summary">
                        <h2>Payment Summary</h2>
                        <div>
                            <span>Payment Type</span>
                            <strong>{paymentTypeLabel(paymentType)}</strong>
                        </div>
                        <div>
                            <span>Payment Status</span>
                            <strong>{paymentStatusLabel(paymentStatus)}</strong>
                        </div>
                        <div>
                            <span>Due Now</span>
                            <strong>{formatPrice(paymentAmount)}</strong>
                        </div>
                        <footer>
                            <span>Total Booking</span>
                            <strong>{formatPrice(totalAmount)}</strong>
                        </footer>
                    </section>

                    <div className="payment-side-note">
                        <strong>{branch.name}</strong>
                        <span>{bookingDateTimeLabel(booking)}</span>
                    </div>
                </aside>
            </div>
        </section>
    );
}

function AuthModal({ mode, setMode, onClose, authLoading, authError, authFieldError, loginForm, setLoginForm, registerForm, setRegisterForm, onLogin, onRegister }) {
    return (
        <div className="modal-backdrop" role="presentation">
            <section className="modal" role="dialog" aria-modal="true" aria-labelledby="customerAuthTitle">
                <button className="modal-close" type="button" onClick={onClose} aria-label="Close form">x</button>
                <div className="modal-head">
                    <span><Icon name="users" size={24} /></span>
                    <div>
                        <h2 id="customerAuthTitle">{mode === 'login' ? 'Login Customer' : 'Customer Registration'}</h2>
                        <p>{mode === 'login' ? 'Sign in to submit and view bookings.' : 'Create a customer account to book salons.'}</p>
                    </div>
                </div>

                <div className="auth-tabs">
                    <button className={mode === 'login' ? 'active' : ''} type="button" onClick={() => setMode('login')}>Login</button>
                    <button className={mode === 'register' ? 'active' : ''} type="button" onClick={() => setMode('register')}>Register</button>
                </div>

                {authError && <div className="alert error">{authError}</div>}

                {mode === 'login' ? (
                    <form className="auth-form" onSubmit={onLogin}>
                        <label>Email<input type="email" value={loginForm.email} onChange={(event) => setLoginForm({ ...loginForm, email: event.target.value })} required />{authFieldError('email') && <small>{authFieldError('email')}</small>}</label>
                        <label>Password<input type="password" value={loginForm.password} onChange={(event) => setLoginForm({ ...loginForm, password: event.target.value })} required />{authFieldError('password') && <small>{authFieldError('password')}</small>}</label>
                        <button className="btn btn-primary auth-submit" type="submit" disabled={authLoading}>{authLoading ? 'Login...' : 'Login'}</button>
                    </form>
                ) : (
                    <form className="auth-form" onSubmit={onRegister}>
                        <label>Full name<input value={registerForm.name} onChange={(event) => setRegisterForm({ ...registerForm, name: event.target.value })} required />{authFieldError('name') && <small>{authFieldError('name')}</small>}</label>
                        <label>Email<input type="email" value={registerForm.email} onChange={(event) => setRegisterForm({ ...registerForm, email: event.target.value })} required />{authFieldError('email') && <small>{authFieldError('email')}</small>}</label>
                        <label>WhatsApp number<input value={registerForm.phone_number} onChange={(event) => setRegisterForm({ ...registerForm, phone_number: event.target.value })} />{authFieldError('phone_number') && <small>{authFieldError('phone_number')}</small>}</label>
                        <label>Gender<select value={registerForm.gender} onChange={(event) => setRegisterForm({ ...registerForm, gender: event.target.value })}><option value="">Choose gender</option><option value="female">Female</option><option value="male">Male</option><option value="other">Other</option></select>{authFieldError('gender') && <small>{authFieldError('gender')}</small>}</label>
                        <label>Password<input type="password" value={registerForm.password} onChange={(event) => setRegisterForm({ ...registerForm, password: event.target.value })} required />{authFieldError('password') && <small>{authFieldError('password')}</small>}</label>
                        <label>Confirm Password<input type="password" value={registerForm.password_confirmation} onChange={(event) => setRegisterForm({ ...registerForm, password_confirmation: event.target.value })} required /></label>
                        <button className="btn btn-primary auth-submit" type="submit" disabled={authLoading}>{authLoading ? 'Creating account...' : 'Register'}</button>
                    </form>
                )}
            </section>
        </div>
    );
}

export default App;
