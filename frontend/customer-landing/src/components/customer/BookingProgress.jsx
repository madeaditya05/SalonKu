import { Icon } from '../Icons.jsx';

const progressSteps = [
    ['Location', 'Choose salon location', 'pin'],
    ['Services', 'Choose services', 'beauty'],
    ['Staff', 'Choose your favorite staff', 'users'],
    ['Schedule', 'Choose date & time', 'calendar'],
    ['Payment', 'Complete payment', 'card'],
];

export function BookingProgress({ activeStep = 0 }) {
    return (
        <nav className="booking-progress" aria-label="Booking progress">
            {progressSteps.map(([title, subtitle, icon], index) => {
                const status = index < activeStep ? 'done' : index === activeStep ? 'active' : '';

                return (
                    <div className={`booking-progress-item ${status}`} key={title}>
                        <span className="booking-progress-mark">
                            {index < activeStep ? <Icon name="check" size={16} /> : index + 1}
                        </span>
                        <span className="booking-progress-copy">
                            <strong>{title}</strong>
                            <small>{subtitle}</small>
                        </span>
                        <Icon className="booking-progress-icon" name={icon} size={18} />
                    </div>
                );
            })}
        </nav>
    );
}
