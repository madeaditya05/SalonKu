import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { customerDateLocale } from '../../i18n.jsx';
import { Icon } from '../Icons.jsx';

function formatPrice(price) {
    return `Rp${Number(price || 0).toLocaleString('id-ID')}`;
}

function formatDateLabel(dateValue) {
    return new Intl.DateTimeFormat(customerDateLocale(), {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(dateValue));
}

function formatWaitLabel(label) {
    return String(label || '').replace(/\bmenit\b/gi, 'minutes');
}

function sameId(left, right) {
    return String(left) === String(right);
}

function dateInputValue(date) {
    const nextDate = new Date(date);
    nextDate.setMinutes(nextDate.getMinutes() - nextDate.getTimezoneOffset());

    return nextDate.toISOString().slice(0, 10);
}

function nextDateOptions() {
    const today = new Date();

    return Array.from({ length: 7 }, (_, index) => {
        const date = new Date(today);
        date.setDate(today.getDate() + index);

        return {
            value: dateInputValue(date),
            month: new Intl.DateTimeFormat(customerDateLocale(), { month: 'short' }).format(date),
            day: new Intl.DateTimeFormat(customerDateLocale(), { day: '2-digit' }).format(date),
            weekday: new Intl.DateTimeFormat(customerDateLocale(), { weekday: 'short' }).format(date),
            badge: index === 0 ? 'Today' : index === 1 ? 'Tomorrow' : '',
        };
    });
}

function uniqueImages(images) {
    return Array.from(new Set(images.filter(Boolean)));
}

function fallbackGallery(images) {
    if (images.length === 0) return [];

    return Array.from({ length: 4 }, (_, index) => images[index % images.length]);
}

function ratingStars(rating) {
    const score = Math.max(0, Math.min(5, Math.round(Number(rating || 0))));

    return Array.from({ length: 5 }, (_, index) => index < score);
}

export function BranchDetailContent({
    branch,
    services,
    allServices = [],
    categories,
    serviceCategory,
    setServiceCategory,
    selectedServiceIds,
    toggleService,
    bookingDate,
    setBookingDate,
    bookingType,
    setBookingType,
    staffs = [],
    selectedStaffId,
    setSelectedStaffId,
    selectedStaff,
    availabilityLoading,
    visibleSlots = [],
    startTime,
    setStartTime,
    queueEstimation,
    canContinueToPayment,
    totals = { price: 0, duration: 0 },
}) {
    const summaryAnchorRef = useRef(null);
    const summaryPinRef = useRef(null);
    const [summaryPin, setSummaryPin] = useState({
        pinned: false,
        left: 0,
        top: 92,
        width: 0,
        height: 0,
    });
    const selectedServices = allServices.filter((service) => selectedServiceIds.some((serviceId) => sameId(serviceId, service.id)));
    const selectedDateLabel = bookingDate ? formatDateLabel(bookingDate) : '-';
    const canContinue = Boolean(canContinueToPayment);
    const dateOptions = nextDateOptions();
    const hasSelectedServices = selectedServiceIds.length > 0;
    const hasScheduled = selectedServices.length > 0 && selectedServices.every((service) => service.isScheduledEnabled);
    const hasQueue = selectedServices.length > 0 && selectedServices.every((service) => service.isQueueEnabled);
    const galleryImages = fallbackGallery(uniqueImages([
        ...(branch?.galleryImages || []),
        branch?.image,
        ...allServices.map((service) => service.image),
    ]));
    const servicePrices = allServices.map((service) => Number(service.price || 0)).filter((price) => price > 0);
    const minServicePrice = servicePrices.length > 0 ? Math.min(...servicePrices) : Number(branch?.minPrice || 0);
    const displayPrice = Number(branch?.minPrice || minServicePrice || 0);
    const rating = Number(branch?.rating || 4.8).toFixed(1);
    const address = branch?.address || branch?.locationLabel || 'Salon address is not available';
    const mapQuery = branch?.latitude && branch?.longitude
        ? `${branch.latitude},${branch.longitude}`
        : address;
    const mapUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(mapQuery)}`;
    const activeServiceCount = branch?.servicesCount || allServices.length || services.length;
    const categoryNames = allServices.length > 0
        ? Array.from(new Set(allServices.map((service) => service.category).filter(Boolean))).slice(0, 6)
        : ['Beauty', 'Treatment'];
    const paymentItems = [
        branch?.supportsPayAtSalon ? 'Pay at salon' : 'Online payment',
        selectedServices.some((service) => service.requiresDp) ? 'Some services require a down payment' : 'No down payment for selected services',
        'Price follows the selected services',
    ];
    const staffItems = staffs.length > 0
        ? staffs.slice(0, 3).map((staff) => staff.name)
        : ['Any Available Staff'];
    const scheduleItems = [
        `Open ${branch?.workingStart || '09:00'} - ${branch?.workingEnd || '18:00'}`,
        branch?.nextAvailableSlot ? `Next slot ${branch.nextAvailableSlot}` : 'Choose a slot from the calendar',
        hasQueue ? 'Supports today queue' : 'Scheduled booking',
    ];
    const bookingLabel = bookingType === 'scheduled'
        ? (startTime ? `${selectedDateLabel}, ${startTime}` : 'Choose an available slot')
        : (formatWaitLabel(queueEstimation?.label) || 'Today queue');
    const reviewBars = [85, 75, 60, 35, 15];

    useEffect(() => {
        let frame = 0;

        function pinOffset() {
            if (window.innerWidth <= 720) return 132;
            if (window.innerWidth <= 1260) return 84;

            return 92;
        }

        function updateSummaryPin() {
            frame = 0;

            const anchor = summaryAnchorRef.current;
            const summary = summaryPinRef.current;
            const layout = anchor?.closest('.hotel-detail-layout');

            if (!anchor || !summary || !layout) {
                setSummaryPin((current) => (current.pinned ? { pinned: false, left: 0, top: 92, width: 0, height: 0 } : current));
                return;
            }

            const anchorRect = anchor.getBoundingClientRect();
            const layoutRect = layout.getBoundingClientRect();
            const top = pinOffset();
            const summaryHeight = summary.offsetHeight;
            const availableHeight = window.innerHeight - top - 16;
            const shouldPin = anchorRect.top <= top && layoutRect.bottom > top + Math.min(summaryHeight, availableHeight);
            const next = {
                pinned: shouldPin,
                left: Math.round(anchorRect.left),
                top,
                width: Math.round(anchorRect.width),
                height: Math.round(summaryHeight),
            };

            setSummaryPin((current) => (
                current.pinned === next.pinned
                && current.left === next.left
                && current.top === next.top
                && current.width === next.width
                && current.height === next.height
                    ? current
                    : next
            ));
        }

        function requestUpdate() {
            if (frame) return;
            frame = window.requestAnimationFrame(updateSummaryPin);
        }

        updateSummaryPin();
        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);

        return () => {
            if (frame) window.cancelAnimationFrame(frame);
            window.removeEventListener('scroll', requestUpdate);
            window.removeEventListener('resize', requestUpdate);
        };
    }, [bookingType, selectedServices.length, selectedStaff?.id, startTime, totals.duration]);

    return (
        <section className="branch-detail-page hotel-detail-page">
            <section className="hotel-detail-hero">
                <div className="hotel-title-row">
                    <div>
                        <h1>{branch?.name || 'Salon Branch'}</h1>
                        <p className="hotel-address-row">
                            <Icon name="pin" size={24} />
                            <span>{address}</span>
                            <a href={mapUrl} target="_blank" rel="noreferrer">
                                <Icon name="eye" size={20} />
                                View On Map
                            </a>
                        </p>
                    </div>

                    <div className="hotel-action-row">
                        <button type="button" aria-label="Save salon">
                            <Icon name="heart" size={22} />
                        </button>
                        <button type="button" aria-label="Share salon">
                            <Icon name="share" size={22} />
                        </button>
                    </div>
                </div>

                <div className="hotel-gallery-grid" aria-label="Salon gallery">
                    {galleryImages.map((image, index) => (
                        <figure className={`${index === 0 ? 'hotel-gallery-main' : ''} ${index === 1 ? 'hotel-gallery-wide' : ''}`} key={`${image}-${index}`}>
                            <img src={image} alt={`${branch?.name || 'Salon'} ${index + 1}`} />
                            {index === 3 && (
                                <button type="button" className="hotel-gallery-view">
                                    View all
                                </button>
                            )}
                        </figure>
                    ))}
                </div>
            </section>

            <div className="hotel-detail-layout">
                <main className="hotel-detail-main">
                    <section className="hotel-section" id="overview">
                        <h2>About This Salon</h2>
                        <div className="hotel-section-divider" />

                        <h3>Main Highlights</h3>
                        <div className="hotel-highlight-grid">
                            <span><Icon name="beauty" size={32} /></span>
                            <span><Icon name="users" size={32} /></span>
                            <span><Icon name="calendar" size={32} /></span>
                            <span><Icon name="card" size={32} /></span>
                        </div>

                        <p>
                            {branch?.name || 'This salon'} provides professional services with staff and schedule options you can set before booking.
                            This branch is located in {branch?.locationLabel || 'your selected area'} with operating hours {branch?.workingStart || '09:00'} - {branch?.workingEnd || '18:00'}.
                        </p>
                        <p>
                            Choose available services, select your favorite staff, then continue with an open time slot.
                            The booking summary appears on the card on the right so it is easy to review before payment.
                        </p>

                        <h3>Advantages</h3>
                        <div className="hotel-check-list">
                            <span><Icon name="check" size={18} /> {activeServiceCount} active services from the salon catalog</span>
                            <span><Icon name="check" size={18} /> Choose staff and schedule slots directly</span>
                            <span><Icon name="check" size={18} /> Estimated price and duration appear before continuing</span>
                        </div>
                    </section>

                    <section className="hotel-section" id="amenities">
                        <h2>Amenities</h2>
                        <div className="hotel-section-divider" />

                        <div className="hotel-amenity-grid">
                            <article>
                                <h3><Icon name="beauty" size={24} /> Services</h3>
                                {categoryNames.map((category) => (
                                    <span key={category}><Icon name="check" size={18} /> {category}</span>
                                ))}
                            </article>

                            <article>
                                <h3><Icon name="card" size={24} /> Payment Method</h3>
                                {paymentItems.map((item) => (
                                    <span key={item}><Icon name="check" size={18} /> {item}</span>
                                ))}
                            </article>

                            <article>
                                <h3><Icon name="shield" size={24} /> Staff</h3>
                                {staffItems.map((item) => (
                                    <span key={item}><Icon name="check" size={18} /> {item}</span>
                                ))}
                            </article>

                            <article>
                                <h3><Icon name="clock" size={24} /> Schedule</h3>
                                {scheduleItems.map((item) => (
                                    <span key={item}><Icon name="check" size={18} /> {item}</span>
                                ))}
                            </article>
                        </div>
                    </section>

                    <section className="hotel-section" id="package-options">
                        <div className="hotel-section-title-row">
                            <h2>Service Options</h2>
                            <label className="hotel-category-select">
                                <select value={serviceCategory} onChange={(event) => setServiceCategory(event.target.value)}>
                                    {categories.map((category) => (
                                        <option value={category} key={category}>{category === 'all' ? 'Select Option' : category}</option>
                                    ))}
                                </select>
                                <Icon name="chevron" size={18} />
                            </label>
                        </div>
                        <div className="hotel-section-divider" />

                        {selectedServices.length > 0 && (
                            <section className="hotel-selected-services" aria-label="Selected services">
                                <div className="hotel-selected-services-head">
                                    <div>
                                        <strong>Selected services</strong>
                                        <span>{selectedServices.length} services - total {formatPrice(totals.price)}</span>
                                    </div>
                                    <small>{totals.duration || 0} minutes</small>
                                </div>

                                <div className="hotel-selected-service-list">
                                    {selectedServices.map((service) => (
                                        <article key={service.id}>
                                            <img src={service.image} alt="" />
                                            <div>
                                                <strong>{service.title}</strong>
                                                <span>{formatPrice(service.price)} - {service.duration} minutes</span>
                                            </div>
                                            <button type="button" onClick={() => toggleService(service.id)} aria-label={`Remove ${service.title}`}>
                                                <Icon name="x" size={16} />
                                            </button>
                                        </article>
                                    ))}
                                </div>
                            </section>
                        )}

                        <div className="hotel-service-list">
                            {services.length === 0 ? (
                                <div className="branch-inline-empty">No services are available for this branch yet.</div>
                            ) : (
                                services.map((service) => {
                                    const selected = selectedServiceIds.some((serviceId) => sameId(serviceId, service.id));
                                    const serviceMeta = [
                                        `${service.duration} minutes`,
                                        service.category,
                                        service.isScheduledEnabled ? 'Fixed time' : null,
                                        service.isQueueEnabled ? 'Queue' : null,
                                    ].filter(Boolean).join(' / ');

                                    return (
                                        <article className={`hotel-service-card ${selected ? 'selected' : ''}`} key={service.id}>
                                            <div className="hotel-service-media">
                                                <img src={service.image} alt={service.title} loading="lazy" />
                                                <span>{service.category}</span>
                                                <button type="button" aria-label="Previous"><Icon name="chevron" size={22} /></button>
                                                <button type="button" aria-label="Next"><Icon name="chevron" size={22} /></button>
                                            </div>

                                            <div className="hotel-service-copy">
                                                <div>
                                                    <h3>{service.title}</h3>
                                                    <p>{serviceMeta}</p>
                                                    <span className={service.requiresDp ? 'warning' : ''}>
                                                        {service.requiresDp ? `DP starts at ${formatPrice(service.dpAmount)}` : 'Can be selected for available schedules'}
                                                    </span>
                                                    {service.description && <small>{service.description}</small>}
                                                </div>

                                                <div className="hotel-service-bottom">
                                                    <strong>{formatPrice(service.price)} <span>/{service.duration} minutes</span></strong>
                                                    <button className={selected ? 'btn btn-outline' : 'btn btn-primary'} type="button" onClick={() => toggleService(service.id)}>
                                                        {selected ? 'Remove' : 'Select Service'}
                                                    </button>
                                                </div>
                                            </div>
                                        </article>
                                    );
                                })
                            )}
                        </div>
                    </section>

                    <section className="hotel-section" id="staff-section">
                        <h2>Staff Options</h2>
                        <div className="hotel-section-divider" />

                        {availabilityLoading && hasSelectedServices ? (
                            <div className="branch-inline-empty">Checking matching staff...</div>
                        ) : hasSelectedServices && staffs.length === 0 ? (
                            <div className="branch-inline-empty">No staff are available for this service yet.</div>
                        ) : (
                            <div className="branch-staff-grid">
                                <button className={`branch-staff-card ${selectedStaffId === '' ? 'active' : ''}`} type="button" disabled={!hasSelectedServices} onClick={() => setSelectedStaffId('')}>
                                    <span className="branch-staff-avatar"><Icon name="users" size={22} /></span>
                                    <strong>Any Available Staff</strong>
                                    <small>The system picks the fastest staff member</small>
                                </button>

                                {staffs.map((staff) => (
                                    <button className={`branch-staff-card ${String(selectedStaffId) === String(staff.id) ? 'active' : ''}`} type="button" disabled={!hasSelectedServices} key={staff.id} onClick={() => setSelectedStaffId(String(staff.id))}>
                                        {staff.image ? <img src={staff.image} alt={staff.name} /> : <span className="branch-staff-avatar">{staff.name.slice(0, 1)}</span>}
                                        <strong>{staff.name}</strong>
                                        <small>{staff.rating ? `${staff.rating} rating` : staff.status}</small>
                                    </button>
                                ))}
                            </div>
                        )}
                    </section>

                    <section className="hotel-section" id="schedule-section">
                        <h2>Available Schedule</h2>
                        <div className="hotel-section-divider" />

                        <div className="branch-mode-row">
                            <button className={bookingType === 'scheduled' ? 'active' : ''} type="button" disabled={!hasScheduled} onClick={() => setBookingType('scheduled')}>
                                <Icon name="calendar" size={17} />
                                Fixed time
                            </button>
                            <button className={bookingType === 'queue' ? 'active' : ''} type="button" disabled={!hasQueue} onClick={() => setBookingType('queue')}>
                                <Icon name="users" size={17} />
                                Queue
                            </button>
                        </div>

                        {bookingType === 'scheduled' ? (
                            <>
                                <div className="branch-date-row">
                                    {dateOptions.map((date) => (
                                        <button className={bookingDate === date.value ? 'active' : ''} type="button" disabled={!hasSelectedServices} key={date.value} onClick={() => setBookingDate(date.value)}>
                                            <span>{date.weekday}</span>
                                            <strong>{date.day}</strong>
                                            <small>{date.month}</small>
                                            {date.badge && <b>{date.badge}</b>}
                                        </button>
                                    ))}
                                </div>

                                {availabilityLoading && hasSelectedServices ? (
                                    <div className="branch-inline-empty">Calculating slots...</div>
                                ) : !hasSelectedServices ? (
                                    <div className="branch-inline-empty">Choose services to see the schedule.</div>
                                ) : visibleSlots.length === 0 ? (
                                    <div className="branch-inline-empty">No slots are available for this selection yet.</div>
                                ) : (
                                    <div className="branch-slot-grid">
                                        {visibleSlots.map((slot) => (
                                            <button className={startTime === slot.time ? 'active' : ''} type="button" key={`${slot.time}-${slot.staff_id}`} onClick={() => setStartTime(slot.time)}>
                                                <strong>{slot.time}</strong>
                                                <span>{slot.staff_name}</span>
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </>
                        ) : (
                            <div className="branch-queue-card">
                                <Icon name="users" size={28} />
                                <div>
                                    <span>Estimated wait</span>
                                    <strong>{formatWaitLabel(queueEstimation?.label) || '10 - 25 minutes'}</strong>
                                    <p>{queueEstimation?.waiting_count || 0} customers are waiting.</p>
                                </div>
                            </div>
                        )}
                    </section>

                    <section className="hotel-section hotel-review-section" id="reviews">
                        <h2>Customer Review</h2>
                        <div className="hotel-section-divider" />

                        <div className="hotel-review-summary">
                            <div>
                                <strong>{rating}</strong>
                                <span>Based on branch rating</span>
                                <div className="hotel-stars" aria-label={`${rating} rating`}>
                                    {ratingStars(rating).map((filled, index) => (
                                        <Icon className={filled ? 'filled' : ''} name="star" size={24} key={index} />
                                    ))}
                                </div>
                            </div>
                            <div className="hotel-review-bars">
                                {reviewBars.map((value) => (
                                    <span key={value}>
                                        <i style={{ width: `${value}%` }} />
                                        <b>{value}%</b>
                                    </span>
                                ))}
                            </div>
                        </div>

                        <article className="hotel-review-card">
                            <div className="hotel-review-avatar">J</div>
                            <div>
                                <h3>JasaKu Customer</h3>
                                <p>Neat service, responsive staff, and easy schedule selection from the booking page.</p>
                            </div>
                            <strong>{rating}</strong>
                        </article>
                    </section>
                </main>

                <aside className="hotel-booking-sidebar">
                    <section className="hotel-booking-card">
                        <span>Price Start at</span>
                        <div className="hotel-price-row">
                            <strong>{formatPrice(displayPrice)}</strong>
                            <p>{bookingType === 'scheduled' ? 'per booking' : 'queue today'}</p>
                        </div>

                        <div className="hotel-sidebar-lines">
                            <span><Icon name="arrow" size={18} /> {rating} <span className="hotel-stars inline">{ratingStars(rating).map((filled, index) => <Icon className={filled ? 'filled' : ''} name="star" size={18} key={index} />)}</span></span>
                            <span><Icon name="arrow" size={18} /> {selectedServices.length || activeServiceCount} services available</span>
                            <span><Icon name="arrow" size={18} /> {bookingLabel}</span>
                        </div>
                    </section>

                    <div
                        className="hotel-summary-pin-shell"
                        ref={summaryAnchorRef}
                        style={summaryPin.pinned ? { minHeight: `${summaryPin.height}px` } : undefined}
                    >
                        <div
                            className={`hotel-summary-pin${summaryPin.pinned ? ' is-pinned' : ''}`}
                            ref={summaryPinRef}
                            style={summaryPin.pinned ? {
                                '--summary-pin-left': `${summaryPin.left}px`,
                                '--summary-pin-top': `${summaryPin.top}px`,
                                '--summary-pin-width': `${summaryPin.width}px`,
                            } : undefined}
                        >
                            <section className="hotel-summary-card">
                                <h3>Booking Summary</h3>
                                <div>
                                    <span>Staff</span>
                                    <strong>{selectedStaff?.name || 'Any Available Staff'}</strong>
                                </div>
                                <div>
                                    <span>Services</span>
                                    <strong>{selectedServices.length || 0} selected</strong>
                                </div>
                                {selectedServices.length > 0 && (
                                    <div className="hotel-summary-selected-list">
                                        {selectedServices.map((service) => (
                                            <article key={service.id}>
                                                <span>{service.title}</span>
                                                <button type="button" onClick={() => toggleService(service.id)} aria-label={`Remove ${service.title}`}>
                                                    <Icon name="x" size={14} />
                                                </button>
                                            </article>
                                        ))}
                                    </div>
                                )}
                                <div>
                                    <span>Duration</span>
                                    <strong>{totals.duration || 0} minutes</strong>
                                </div>

                                <Link
                                    className={`hotel-sidebar-cta${canContinue ? '' : ' disabled'}`}
                                    to="/booking/payment"
                                    aria-disabled={!canContinue}
                                    onClick={(event) => {
                                        if (!canContinue) event.preventDefault();
                                    }}
                                >
                                    Continue Booking
                                </Link>
                            </section>
                        </div>
                    </div>

                    <section className="hotel-deal-card">
                        <h3>Today's Best Deal</h3>
                        <div>
                            <img src={galleryImages[1] || branch?.image} alt="" />
                            <article>
                                <strong>Service Plan</strong>
                                <span>Choose multiple services in one booking and schedule them in one step.</span>
                            </article>
                        </div>
                    </section>
                </aside>
            </div>
        </section>
    );
}
