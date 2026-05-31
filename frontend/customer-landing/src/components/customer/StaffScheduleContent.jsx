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

function statusLabel(value) {
    return String(value || 'available').replace(/_/g, ' ');
}

function titleCase(value) {
    return String(value || '')
        .replace(/_/g, ' ')
        .trim()
        .replace(/\b\w/g, (character) => character.toUpperCase());
}

function genderLabel(value) {
    const normalized = String(value || '').toLowerCase();

    if (normalized === 'female' || normalized === 'perempuan') return 'Female';
    if (normalized === 'male' || normalized === 'laki-laki' || normalized === 'laki laki') return 'Male';
    if (normalized === 'other') return 'Other';

    return 'Not specified';
}

function timeFromNow(minutes) {
    const date = new Date();
    date.setMinutes(date.getMinutes() + Number(minutes || 0));

    return new Intl.DateTimeFormat(customerDateLocale(), {
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

function queueArrivalLabel(queueEstimation) {
    const min = Number(queueEstimation?.estimated_wait_min ?? 10);
    const max = Number(queueEstimation?.estimated_wait_max ?? 25);

    return `${timeFromNow(min)} - ${timeFromNow(max)}`;
}

function staffSkillNames(staff) {
    return (Array.isArray(staff?.skills) ? staff.skills : [])
        .map((skill) => skill?.title || skill?.name || skill?.service_name || '')
        .filter(Boolean)
        .slice(0, 3);
}

function staffSkillIds(staff) {
    return (Array.isArray(staff?.skills) ? staff.skills : [])
        .map((skill) => String(skill?.id ?? skill?.service_id ?? skill))
        .filter(Boolean);
}

function staffServiceCoverage(staff, selectedServices) {
    const skillIds = staffSkillIds(staff);

    if (skillIds.length === 0) {
        return {
            handled: [],
            missing: selectedServices,
        };
    }

    return selectedServices.reduce((coverage, service) => {
        const bucket = skillIds.includes(String(service.id)) ? 'handled' : 'missing';

        coverage[bucket].push(service);
        return coverage;
    }, { handled: [], missing: [] });
}

function serviceNames(services) {
    return services
        .map((service) => service?.title || service?.name || '')
        .filter(Boolean);
}

function matchingSkillCount(staff, selectedServices) {
    const serviceIds = selectedServices.map((service) => String(service.id));
    const skillIds = staffSkillIds(staff);

    if (serviceIds.length === 0) return 0;
    if (skillIds.length === 0) return 0;

    return serviceIds.filter((serviceId) => skillIds.includes(serviceId)).length;
}

function dayAliases(dateValue) {
    const date = new Date(dateValue);
    const aliases = [
        ['0', 'sunday', 'sun', 'minggu', 'ahad'],
        ['1', 'monday', 'mon', 'senin'],
        ['2', 'tuesday', 'tue', 'selasa'],
        ['3', 'wednesday', 'wed', 'rabu'],
        ['4', 'thursday', 'thu', 'kamis'],
        ['5', 'friday', 'fri', 'jumat', 'jum\'at'],
        ['6', 'saturday', 'sat', 'sabtu'],
    ];

    return aliases[Number.isNaN(date.getTime()) ? 0 : date.getDay()] || [];
}

function staffScheduleLabel(staff, bookingDate) {
    const schedules = Array.isArray(staff?.schedules) ? staff.schedules : [];

    if (schedules.length === 0) return 'Follows branch hours';

    const aliases = dayAliases(bookingDate);
    const schedule = schedules.find((item) => (
        item?.is_available
        && aliases.includes(String(item.day_of_week || '').toLowerCase())
    ));

    if (!schedule) return 'No shift on this date';

    return `${String(schedule.start_time || '').slice(0, 5)} - ${String(schedule.end_time || '').slice(0, 5)}`;
}

function nextStaffSlot(staff, availableSlots) {
    const slot = (Array.isArray(availableSlots) ? availableSlots : [])
        .find((item) => String(item.staff_id) === String(staff?.id));

    return slot?.time || '';
}

export function StaffScheduleContent({
    branch,
    selectedServices = [],
    bookingType,
    setBookingType,
    staffs = [],
    selectedStaffId,
    setSelectedStaffId,
    selectedStaff,
    bookingDate,
    availabilityLoading,
    availableSlots = [],
    visibleSlots = [],
    startTime,
    setStartTime,
    queueEstimation,
    canContinueToPayment,
    totals = { price: 0, duration: 0 },
    serviceRouteTo,
}) {
    const hasSelectedServices = selectedServices.length > 0;
    const hasStaffOptions = staffs.length > 0;
    const hasVisibleSlots = visibleSlots.length > 0;
    const hasScheduled = hasSelectedServices && selectedServices.every((service) => service.isScheduledEnabled);
    const hasQueue = hasSelectedServices && selectedServices.every((service) => service.isQueueEnabled);
    const selectedDateLabel = bookingDate ? formatDateLabel(bookingDate) : '-';
    const selectedSlotIsVisible = Boolean(startTime) && visibleSlots.some((slot) => slot.time === startTime && !slot.expired);
    const bookingLabel = bookingType === 'scheduled'
        ? (selectedSlotIsVisible ? `${selectedDateLabel}, ${startTime}` : 'Choose an available slot')
        : (formatWaitLabel(queueEstimation?.label) || 'Today queue');
    const queueWaitLabel = formatWaitLabel(queueEstimation?.label) || '10 - 25 minutes';
    const queueCustomers = Number(queueEstimation?.waiting_count || 0);
    const queueArrival = queueArrivalLabel(queueEstimation);
    const staffLabel = selectedStaff?.name || 'Any Available Staff';
    const autoNextSlot = visibleSlots.find((slot) => !slot.expired)?.time || availableSlots[0]?.time || '';
    const staffWithSlotsCount = new Set(availableSlots.map((slot) => String(slot.staff_id))).size;
    const continueReady = Boolean(canContinueToPayment);

    return (
        <section className="staff-schedule-page">
            <section className="staff-schedule-hero">
                <nav className="booking-review-breadcrumb" aria-label="Staff schedule breadcrumb">
                    <Link to="/"><Icon name="store" size={18} /> Home</Link>
                    <span />
                    <Link to={serviceRouteTo}>Services</Link>
                    <span />
                    <strong>Staff & Schedule</strong>
                </nav>
                <div>
                    <div>
                        <span><Icon name="users" size={22} /> Staff & schedule</span>
                        <h1>Choose who handles your visit.</h1>
                        <p>Pick a preferred staff member or let the salon assign the fastest available option, then choose the best time.</p>
                    </div>
                    {branch?.image && <img src={branch.image} alt={branch?.name || 'Salon'} />}
                </div>
            </section>

            <div className="staff-schedule-layout">
                <main className="staff-schedule-main">
                    <section className="staff-schedule-card selected-service-overview">
                        <header>
                            <div>
                                <span>Selected services</span>
                                <h2>{selectedServices.length} services ready</h2>
                            </div>
                            <Link to={serviceRouteTo}><Icon name="chevron" size={16} /> Edit services</Link>
                        </header>
                        <div>
                            {selectedServices.map((service) => (
                                <article key={service.id}>
                                    <img src={service.image || branch?.image} alt="" />
                                    <div>
                                        <strong>{service.title}</strong>
                                        <span>{service.duration} minutes</span>
                                    </div>
                                    <b>{formatPrice(service.price)}</b>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section className="staff-schedule-card">
                        <header>
                            <div>
                                <span>Staff options</span>
                                <h2>Pick your preferred staff</h2>
                            </div>
                            {availabilityLoading && <small>Refreshing availability...</small>}
                        </header>

                        {availabilityLoading && hasSelectedServices && !hasStaffOptions ? (
                            <div className="branch-inline-empty">Checking matching staff...</div>
                        ) : hasSelectedServices && !hasStaffOptions ? (
                            <div className="branch-inline-empty">No staff can handle all selected services on this date.</div>
                        ) : (
                            <div className="staff-detail-grid">
                                <button
                                    className={`staff-detail-card is-auto ${selectedStaffId === '' ? 'active' : ''}`}
                                    type="button"
                                    onClick={() => setSelectedStaffId('')}
                                >
                                    <span className="branch-staff-avatar staff-card-avatar"><Icon name="users" size={26} /></span>
                                    <div className="staff-card-content">
                                        <div className="staff-card-title-row">
                                            <strong>Any Available Staff</strong>
                                            <b>Recommended</b>
                                        </div>
                                        <p>The salon assigns the fastest eligible staff member for your selected time.</p>
                                        <div className="staff-card-chips">
                                            <span>Auto assign</span>
                                            <span>{staffWithSlotsCount || staffs.length} staff ready</span>
                                            <span>{autoNextSlot ? `Next ${autoNextSlot}` : 'Waiting for slot'}</span>
                                        </div>
                                        <div className="staff-card-facts">
                                            <article><small>Best for</small><strong>Fastest slot</strong></article>
                                            <article><small>Gender</small><strong>Any</strong></article>
                                            <article><small>Skill match</small><strong>{selectedServices.length}/{selectedServices.length}</strong></article>
                                        </div>
                                    </div>
                                </button>

                                {staffs.map((staff) => {
                                    const skills = staffSkillNames(staff);
                                    const coverage = staffServiceCoverage(staff, selectedServices);
                                    const handledServiceNames = serviceNames(coverage.handled);
                                    const matchCount = matchingSkillCount(staff, selectedServices);
                                    const nextSlot = nextStaffSlot(staff, availableSlots);
                                    const scheduleLabel = staffScheduleLabel(staff, bookingDate);
                                    const gender = genderLabel(staff.gender);
                                    const bio = staff.bio || (handledServiceNames.length > 0 ? `Handles ${handledServiceNames.join(', ')}.` : 'Eligible for selected services.');

                                    return (
                                        <button
                                            className={`staff-detail-card ${String(selectedStaffId) === String(staff.id) ? 'active' : ''}`}
                                            type="button"
                                            key={staff.id}
                                            onClick={() => setSelectedStaffId(String(staff.id))}
                                        >
                                            {staff.image ? <img className="staff-card-avatar" src={staff.image} alt={staff.name} /> : <span className="branch-staff-avatar staff-card-avatar">{staff.name.slice(0, 1)}</span>}
                                            <div className="staff-card-content">
                                                <div className="staff-card-title-row">
                                                    <strong>{staff.name}</strong>
                                                    <b>{titleCase(staff.status)}</b>
                                                </div>
                                                <p>{bio}</p>
                                                <div className="staff-card-chips">
                                                    <span>{gender}</span>
                                                    <span>{staff.rating ? `${Number(staff.rating).toFixed(1)} rating` : 'New staff'}</span>
                                                    <span>{nextSlot ? `Next ${nextSlot}` : 'No slot yet'}</span>
                                                </div>
                                                <div className="staff-card-facts">
                                                    <article><small>Shift</small><strong>{scheduleLabel}</strong></article>
                                                    <article><small>Selected services</small><strong>{matchCount}/{selectedServices.length}</strong></article>
                                                    <article><small>Focus</small><strong>{handledServiceNames[0] || skills[0] || staff.role || 'Beauty service'}</strong></article>
                                                </div>
                                                <span className="staff-skill-line">
                                                    {handledServiceNames.length > 0 ? `Handles: ${handledServiceNames.join(' / ')}` : 'No selected service match'}
                                                </span>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </section>

                    <section className="staff-schedule-card">
                        <header>
                            <div>
                                <span>Schedule</span>
                                <h2>Choose a visit time</h2>
                            </div>
                            <small>{staffLabel}</small>
                        </header>

                        <div className="staff-locked-date">
                            <span><Icon name="calendar" size={18} /> Visit date from Find Services</span>
                            <strong>{selectedDateLabel}</strong>
                            <Link to="/findservice">Change date</Link>
                        </div>

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
                                {availabilityLoading && hasSelectedServices && !hasVisibleSlots ? (
                                    <div className="staff-slot-skeleton" aria-label="Preparing available slots">
                                        {Array.from({ length: 8 }).map((_, index) => <span key={index} />)}
                                    </div>
                                ) : !hasVisibleSlots ? (
                                    <div className="branch-inline-empty">No slots are available for this selection yet.</div>
                                ) : (
                                    <div className="branch-slot-grid staff-schedule-slots">
                                        {visibleSlots.map((slot) => {
                                            const expired = Boolean(slot.expired);
                                            const active = startTime === slot.time && !expired;
                                            const slotLabel = expired ? 'Passed' : (selectedStaffId ? slot.staff_name : 'Available');

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
                                                    <span>{slotLabel}</span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}
                            </>
                        ) : (
                            <div className="queue-experience-card">
                                <div className="queue-experience-mark"><Icon name="users" size={28} /></div>
                                <div>
                                    <span>Estimated wait</span>
                                    <strong>{queueWaitLabel}</strong>
                                    <p>{queueCustomers} customers are waiting in the active queue.</p>
                                </div>
                                <article>
                                    <span>Arrive around</span>
                                    <strong>{queueArrival}</strong>
                                </article>
                                <article>
                                    <span>Queue mode</span>
                                    <strong>{selectedStaff ? selectedStaff.name : 'Fastest staff'}</strong>
                                </article>
                                <small>Come inside this arrival window and the salon can place you near the live queue estimate.</small>
                            </div>
                        )}
                    </section>
                </main>

                <aside className="staff-schedule-summary">
                    <section>
                        <h2>Booking Summary</h2>
                        <div>
                            <span>Salon</span>
                            <strong>{branch?.name || 'Salon Branch'}</strong>
                        </div>
                        <div>
                            <span>Staff</span>
                            <strong>{staffLabel}</strong>
                        </div>
                        <div>
                            <span>Schedule</span>
                            <strong>{bookingLabel}</strong>
                        </div>
                        <div>
                            <span>Duration</span>
                            <strong>{totals.duration || 0} minutes</strong>
                        </div>
                        <footer>
                            <span>Total</span>
                            <strong>{formatPrice(totals.price)}</strong>
                        </footer>
                        <Link
                            className={`hotel-sidebar-cta${continueReady ? '' : ' disabled'}`}
                            to="/booking/payment"
                            aria-disabled={!continueReady}
                            onClick={(event) => {
                                if (!continueReady) event.preventDefault();
                            }}
                        >
                            Review Booking
                        </Link>
                        <Link className="staff-summary-back" to={serviceRouteTo}>
                            Back to Services
                        </Link>
                    </section>
                </aside>
            </div>
        </section>
    );
}
