import { useState } from 'react';
import { Link, NavLink } from 'react-router-dom';
import { resolveAssetUrl } from '../../api.js';
import { useCustomerLanguage } from '../../i18n.jsx';
import { Icon } from '../Icons.jsx';

const mainNavItems = [
    { label: 'Home', to: '/', end: true },
    { label: 'Find Services', to: '/findservice' },
    { label: 'Promo', to: '/promo' },
    { label: 'Articles', to: '/articles' },
    { label: 'For Business', to: '/business' },
];

function avatarUrl(authUser) {
    const profile = authUser?.customer_profile || authUser?.customerProfile || {};
    return resolveAssetUrl(profile.avatar || authUser?.avatar)
        || 'https://images.unsplash.com/photo-1527980965255-d3b416303d12?auto=format&fit=crop&w=120&q=80';
}

function ProfileAvatar({ authUser, displayName }) {
    const image = avatarUrl(authUser);

    if (image) {
        return <img src={image} alt={displayName} />;
    }

    return <span>{displayName.slice(0, 1).toUpperCase()}</span>;
}

export function CustomerTopbar({
    authUser,
    firstName,
    onLogout,
    onOpenBookings,
}) {
    const [isProfileOpen, setProfileOpen] = useState(false);
    const { language, setLanguage } = useCustomerLanguage();
    const displayName = authUser
        ? (authUser.name || firstName?.(authUser.name) || authUser.email || 'Customer')
        : '';
    const displayEmail = authUser?.email || 'customer@email.com';

    function closeAndRun(callback) {
        setProfileOpen(false);
        callback?.();
    }

    return (
        <header className={`site-header booking-site-header ${authUser ? 'is-authenticated' : 'is-guest'}`}>
            <Link className="booking-brand" to="/" aria-label="Booking home">
                <span className="booking-brand-mark"><Icon name="plane" size={25} /></span>
                <strong>Booking</strong>
            </Link>

            <nav className="booking-main-nav" aria-label="Main navigation">
                {mainNavItems.map((item) => (
                    <NavLink
                        key={item.to}
                        to={item.to}
                        end={item.end}
                        className={({ isActive }) => (isActive ? 'active' : '')}
                    >
                        {item.label}
                    </NavLink>
                ))}
            </nav>

            <div className="booking-header-actions">
                {!authUser ? (
                    <>
                        <Link className="booking-auth-button" to="/signin">Sign In</Link>
                        <Link className="booking-auth-button primary" to="/signup">Sign Up</Link>
                    </>
                ) : (
                    <>
                        <button className="booking-bell" type="button" aria-label="Notifications">
                            <Icon name="bell" size={19} />
                            <span aria-hidden="true" />
                        </button>
                        <div
                            className={`booking-profile-menu ${isProfileOpen ? 'is-open' : ''}`}
                            onMouseEnter={() => setProfileOpen(true)}
                            onMouseLeave={() => setProfileOpen(false)}
                        >
                            <button
                                className="booking-profile-trigger"
                                type="button"
                                aria-expanded={isProfileOpen}
                                onClick={() => setProfileOpen((current) => !current)}
                            >
                                <ProfileAvatar authUser={authUser} displayName={displayName} />
                            </button>

                            <section className="booking-profile-dropdown">
                                <div className="booking-profile-head">
                                    <span className="booking-profile-avatar xl">
                                        <ProfileAvatar authUser={authUser} displayName={displayName} />
                                    </span>
                                    <div>
                                        <strong>{displayName}</strong>
                                        <small>{displayEmail}</small>
                                    </div>
                                </div>

                                <div className="booking-profile-links">
                                    <button type="button" onClick={() => closeAndRun(onOpenBookings)}><Icon name="bookmark" size={18} /> My Bookings</button>
                                    <button type="button"><Icon name="heart" size={18} /> My Wishlist</button>
                                    <button type="button"><Icon name="gear" size={18} /> Settings</button>
                                    <button type="button"><Icon name="info" size={18} /> Help Center</button>
                                    <button type="button" onClick={() => closeAndRun(onLogout)}><Icon name="power" size={18} /> Sign Out</button>
                                </div>

                                <div className="booking-profile-mode">
                                    <span>Mode:</span>
                                    <button className="active" type="button" aria-label="Light mode"><Icon name="sun" size={18} /></button>
                                    <button type="button" aria-label="Dark mode"><Icon name="moon" size={18} /></button>
                                    <button type="button" aria-label="System mode"><span /></button>
                                </div>

                                <div className="booking-profile-language">
                                    <span>Language:</span>
                                    <div className="booking-language-toggle" role="group" aria-label="Language">
                                        <button
                                            className={language === 'en' ? 'active' : ''}
                                            type="button"
                                            aria-label="English"
                                            onClick={() => setLanguage('en')}
                                        >
                                            ENG
                                        </button>
                                        <button
                                            className={language === 'id' ? 'active' : ''}
                                            type="button"
                                            aria-label="Indonesia"
                                            onClick={() => setLanguage('id')}
                                        >
                                            ID
                                        </button>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </>
                )}
            </div>
        </header>
    );
}
