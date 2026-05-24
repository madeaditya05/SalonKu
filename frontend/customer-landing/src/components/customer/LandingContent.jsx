import { useState } from 'react';
import { Icon } from '../Icons.jsx';

const heroImage = 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=1100&q=88';
const storyImage = 'https://images.unsplash.com/photo-1600948836101-f9ffda59d250?auto=format&fit=crop&w=220&q=80';

const promoCards = [
    {
        title: 'First Booking Deal',
        text: 'Save up to 20% on selected salon services',
        image: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=360&q=80',
    },
    {
        title: 'Hair Care Week',
        text: 'Hair spa, blowout, and treatment packages starting today',
        image: 'https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=360&q=80',
    },
    {
        title: 'Beauty Flash Slot',
        text: 'Find quick slots for manicures and facials',
        image: 'https://images.unsplash.com/photo-1607779097040-26e80aa78e66?auto=format&fit=crop&w=360&q=80',
    },
];

const featuredSalons = [
    ['Glow Theory Salon', 'Jakarta Selatan', 'Rp85K', '4.9', 'https://images.unsplash.com/photo-1600948836101-f9ffda59d250?auto=format&fit=crop&w=620&q=86'],
    ['Lumi Hair Studio', 'Bandung', 'Rp120K', '4.8', 'https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=620&q=86'],
    ['Nail & Beauty Lab', 'Surabaya', 'Rp75K', '4.7', 'https://images.unsplash.com/photo-1607779097040-26e80aa78e66?auto=format&fit=crop&w=620&q=86'],
    ['Serene Spa House', 'Bali', 'Rp150K', '4.9', 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&w=620&q=86'],
];

const commonsImage = (fileName) => `https://commons.wikimedia.org/wiki/Special:FilePath/${encodeURIComponent(fileName)}?width=420`;

const nearbyPlaces = [
    ['Jakarta', '120+ active salons', commonsImage('Around Monas Jakarta (2025).jpg'), 'Monas Jakarta'],
    ['Bandung', '80+ beauty services', commonsImage('Gedung Sate Bandung.jpg'), 'Gedung Sate Bandung'],
    ['Surabaya', '90+ salon branches', commonsImage('Tugu Pahlawan.jpg'), 'Tugu Pahlawan Surabaya'],
    ['Yogyakarta', '45+ treatment', commonsImage('Tugu of Yogyakarta 002.jpg'), 'Tugu Yogyakarta'],
    ['Bali', 'spa and massage', commonsImage('Brantan Bali Pura-Ulun-Danu-Bratan-01.jpg'), 'Pura Ulun Danu Beratan Bali'],
    ['Medan', 'hair studio', commonsImage('Istana Maimun, Medan.jpg'), 'Istana Maimun Medan'],
    ['Makassar', 'nail care', commonsImage('Masjid 99 Kubah Asmaul Husna Makassar 11.jpg'), 'Masjid 99 Kubah Makassar'],
    ['Semarang', 'facial clinic', commonsImage('Lawang Sewu Semarang Indonesia 2.jpg'), 'Lawang Sewu Semarang'],
    ['Depok', 'barber and salon', commonsImage('Menara Masjid Dian Al Mahri.jpg'), 'Masjid Dian Al Mahri Depok'],
    ['Tangerang', 'quick grooming', commonsImage("Masjid Raya Al A'zhom Kota Tangerang Banten.jpg"), "Masjid Raya Al A'zhom Tangerang"],
    ['Bekasi', 'makeup artist', commonsImage('Patriot Stadium Bekasi.jpg'), 'Stadion Patriot Candrabhaga Bekasi'],
    ['Malang', 'wellness studio', commonsImage('Balai Kota Malang - panoramio.jpg'), 'Balai Kota Malang'],
];

const partnerLogos = ['GlowBar', 'HairLab', 'Nailish', 'Serene Spa', 'BeautyPro', 'StyleHub'];

function todayInputValue() {
    const today = new Date();
    today.setMinutes(today.getMinutes() - today.getTimezoneOffset());

    return today.toISOString().slice(0, 10);
}

function RatingStars() {
    return (
        <span className="booking-stars" aria-label="5 stars">
            {Array.from({ length: 5 }).map((_, index) => (
                <Icon key={index} name="star" size={17} />
            ))}
        </span>
    );
}

export function LandingContent({
    locationQuery,
    setLocationQuery,
    submitLocation,
    isBooting,
    bookingDate,
    setBookingDate,
    searchError,
    useCurrentLocation,
    currentCoords,
    setCurrentCoords,
}) {
    const [isLocating, setLocating] = useState(false);
    const minBookingDate = todayInputValue();
    const formBookingDate = bookingDate || minBookingDate;

    async function useCurrentPosition() {
        setLocating(true);

        try {
            await useCurrentLocation();
        } finally {
            setLocating(false);
        }
    }

    return (
        <section className="booking-home">
            <section className="booking-hero-section">
                <div className="booking-hero-copy">
                    <h1>Find the top Salons nearby.</h1>
                    <span className="booking-hero-underline" />
                    <p>Book haircuts, hair spas, manicures, facials, and favorite treatments from nearby salons with clear schedules.</p>
                    <div className="booking-hero-actions">
                        <button className="booking-primary-action" type="button">Search Salons</button>
                        <button className="booking-story-action" type="button">
                            <span>
                                <img src={storyImage} alt="" />
                                <b><Icon name="play" size={16} /></b>
                            </span>
                            See how booking works
                        </button>
                    </div>
                </div>

                <div className="booking-hero-art">
                    <img className="booking-hero-main-img" src={heroImage} alt="Salon interior with styling chairs" />
                    <img className="booking-float-thumb thumb-one" src="https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=180&q=80" alt="" />
                    <img className="booking-float-thumb thumb-two" src="https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=180&q=80" alt="" />
                    <div className="booking-support-card">
                        <span><Icon name="headset" size={42} /></span>
                        <strong>24 / 7</strong>
                        <small>Booking Support</small>
                    </div>
                    <div className="booking-confetti" aria-hidden="true" />
                </div>

                <form className="booking-availability" onSubmit={submitLocation}>
                    <h2>Search Salons</h2>
                    <label className="booking-location-field">
                        <Icon name="pin" size={38} />
                        <span>
                            <small>Location</small>
                            <input
                                name="location"
                                value={locationQuery}
                                onChange={(event) => {
                                    setCurrentCoords(null);
                                    setLocationQuery(event.target.value);
                                }}
                                placeholder="Choose location"
                            />
                            <input name="lat" type="hidden" value={currentCoords?.lat || ''} />
                            <input name="lng" type="hidden" value={currentCoords?.lng || ''} />
                            <button className="current-location-button" type="button" onClick={useCurrentPosition} disabled={isLocating}>
                                {isLocating ? 'Searching' : currentCoords ? 'Active' : 'Current'}
                            </button>
                        </span>
                    </label>
                    <label className="booking-schedule-field">
                        <Icon name="calendar" size={38} />
                        <span>
                            <small>Date</small>
                            <input
                                className="salon-date-field"
                                name="booking_date"
                                type="date"
                                min={minBookingDate}
                                value={formBookingDate}
                                onChange={(event) => setBookingDate(event.target.value)}
                            />
                        </span>
                    </label>
                    <button type="submit" disabled={isBooting} aria-label="Search salons">
                        <Icon name="search" size={32} />
                    </button>
                    {searchError && <p className="search-date-error" role="alert">{searchError}</p>}
                </form>
            </section>

            <section className="booking-promos" id="promo">
                <button className="promo-arrow is-prev" type="button" aria-label="Previous">
                    <Icon name="arrow" size={26} />
                </button>
                {promoCards.map((promo) => (
                    <article className="booking-promo-card" key={promo.title}>
                        <img src={promo.image} alt="" />
                        <div>
                            <h2>{promo.title}</h2>
                            <p>{promo.text}</p>
                        </div>
                    </article>
                ))}
                <button className="promo-arrow right" type="button" aria-label="Next">
                    <Icon name="arrow" size={26} />
                </button>
            </section>

            <section className="booking-holiday">
                <div className="holiday-image">
                    <img src="https://images.unsplash.com/photo-1521590832167-7bcbfaa6381f?auto=format&fit=crop&w=760&q=88" alt="Salon stylist preparing a client" />
                </div>
                <div className="holiday-copy">
                    <h2>Your Best Beauty Day Starts Here!</h2>
                    <p>Choose a branch, services, staff, and schedule without back-and-forth chat. All price and duration details are visible before booking.</p>
                    <div className="holiday-feature-grid">
                        <div><span><Icon name="shield" size={28} /></span><strong>Verified Salon</strong><small>Active branches and services from registered providers.</small></div>
                        <div><span><Icon name="clock" size={28} /></span><strong>Slot Real Time</strong><small>Check staff schedules and queues before creating a booking.</small></div>
                    </div>
                </div>
            </section>

            <section className="featured-hotels" id="articles">
                <h2>Featured Salons</h2>
                <div className="featured-hotel-grid">
                    {featuredSalons.map(([name, city, price, rating, image]) => (
                        <article className="featured-hotel-card" key={name}>
                            <div>
                                <img src={image} alt={name} />
                                <span><Icon name="pin" size={18} /> {city}</span>
                            </div>
                            <h3>{name}</h3>
                            <p><strong>{price}</strong> /from <b>{rating} <Icon name="star" size={19} /></b></p>
                        </article>
                    ))}
                </div>
            </section>

            <section className="booking-logo-strip">
                {partnerLogos.map((logo) => <span key={logo}>{logo}</span>)}
            </section>

            <section className="booking-testimonial">
                <button type="button" aria-label="Previous testimonial" className="is-prev"><Icon name="arrow" size={26} /></button>
                <div className="testimonial-photo">
                    <img src="https://images.unsplash.com/photo-1522338242992-e1a54906a8da?auto=format&fit=crop&w=520&q=88" alt="Happy salon customer" />
                    <span>!</span>
                </div>
                <div className="testimonial-copy">
                    <b>"</b>
                    <p>Salon booking is much easier. I can choose services, staff, and open times without waiting for chat replies.</p>
                    <RatingStars />
                    <strong>Nadia Putri</strong>
                    <small>Customer GlowHub</small>
                </div>
                <button type="button" aria-label="Next testimonial"><Icon name="arrow" size={26} /></button>
            </section>

            <section className="explore-nearby">
                <h2>Explore Salon Cities</h2>
                <div className="nearby-grid">
                    {nearbyPlaces.map(([place, time, image, landmark]) => (
                        <article key={`${place}-${time}`}>
                            <img src={image} alt={landmark} loading="lazy" decoding="async" />
                            <h3>{place}</h3>
                            <p>{time}</p>
                        </article>
                    ))}
                </div>
            </section>

            <section className="booking-support-strip" id="business">
                <article>
                    <span><Icon name="heart" size={38} /></span>
                    <div>
                        <h2>Booking Help</h2>
                        <p>Help for finding salons, changing schedules, and viewing booking status.</p>
                    </div>
                </article>
                <article>
                    <span><Icon name="money" size={38} /></span>
                    <div>
                        <h2>Clear Payment</h2>
                        <p>Choose pay at salon, down payment, or full payment based on the service policy.</p>
                    </div>
                </article>
                <div className="download-app">
                    <h2>For salon business</h2>
                    <span>Manage branches</span>
                    <span>Set staff and schedules</span>
                </div>
            </section>
        </section>
    );
}
