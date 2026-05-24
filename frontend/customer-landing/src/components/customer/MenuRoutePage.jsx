import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { getCoupons } from '../../api.js';
import { customerDateLocale } from '../../i18n.jsx';
import { Icon } from '../Icons.jsx';

const providerFrontendUrl = import.meta.env.VITE_PROVIDER_FRONTEND_URL || '/provider';

const routePages = {
    promo: {
        className: 'promo',
        kicker: 'Promo',
        title: 'Salon promos for your next booking',
        description: 'Find vouchers and salon offers you can use at checkout. Choose services, check schedules, then enter the promo code on the payment page.',
        image: 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=1100&q=86',
        primaryAction: { label: 'Find Promo Services', to: '/findservice' },
        secondaryAction: { label: 'Create Account', to: '/signup' },
        stats: [
            ['5%', 'transparent tax'],
            ['Valid', 'checked at checkout'],
            ['Fast', 'instant booking'],
        ],
        cards: [
            {
                icon: 'money',
                title: 'FIRSTBOOK',
                text: 'New customer promo for the first booking. Enter the code during payment review.',
            },
            {
                icon: 'calendar',
                title: 'WEEKDAY20',
                text: 'Weekday offers for available salon slots on your chosen date.',
            },
            {
                icon: 'heart',
                title: 'MEMBER10',
                text: 'A small reward for customers who are logged in and book from the same account.',
            },
        ],
        featureTitle: 'Voucher is validated automatically',
        featureText: 'The system checks the voucher code, validity period, usage limit, and selected services before calculating the total payment.',
    },
    articles: {
        className: 'articles',
        kicker: 'Articles',
        title: 'Short articles before booking a salon',
        description: 'Read quick guides about services, duration, promos, and how to choose a schedule before continuing to the salon catalog.',
        image: 'https://images.unsplash.com/photo-1600948836101-f9ffda59d250?auto=format&fit=crop&w=1100&q=86',
        primaryAction: { label: 'View Salons', to: '/findservice' },
        secondaryAction: { label: 'Check Promos', to: '/promo' },
        stats: [
            ['6', 'selected guides'],
            ['5 min', 'average read'],
            ['Updated', 'follows booking'],
        ],
        cards: [
            {
                icon: 'beauty',
                title: 'How to choose the right salon service',
                meta: 'Beauty guide - 4 min read',
                image: 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=700&q=84',
                text: 'Know your needs, duration, and expected results before adding services to your booking.',
                to: '/findservice',
            },
            {
                icon: 'clock',
                title: 'Fixed-time booking or join the queue?',
                meta: 'Booking tips - 3 min read',
                image: 'https://images.unsplash.com/photo-1516975080664-ed2fc6a32937?auto=format&fit=crop&w=700&q=84',
                text: 'Choose the booking mode that fits the service duration, staff availability, and arrival time.',
                to: '/findservice',
            },
            {
                icon: 'bookmark',
                title: 'How to use a voucher at checkout',
                meta: 'Promo guide - 2 min read',
                image: 'https://images.unsplash.com/photo-1522337660859-02fbefca4702?auto=format&fit=crop&w=700&q=84',
                text: 'Enter the promo code on the payment page and the system will calculate a valid discount.',
                to: '/promo',
            },
        ],
        featureTitle: 'Articles connected to the booking flow',
        featureText: 'After reading, customers can continue finding salons, choosing services, using vouchers, and completing bookings.',
    },
    business: {
        className: 'business',
        kicker: 'For Business',
        title: 'Manage salon branches and bookings from one system',
        description: 'This page is for providers who want to accept online bookings and manage services, staff, schedules, queues, and payments.',
        image: 'https://images.unsplash.com/photo-1521590832167-7bcbfaa6381f?auto=format&fit=crop&w=1100&q=86',
        primaryAction: { label: 'Buka Portal Provider', href: providerFrontendUrl },
        secondaryAction: { label: 'View Customer Flow', to: '/findservice' },
        stats: [
            ['Branch', 'multiple locations'],
            ['Staff', 'skills & schedules'],
            ['Booking', 'queue & schedule'],
        ],
        cards: [
            {
                icon: 'store',
                title: 'Branch management',
                text: 'Set locations, operating hours, photos, and service information for each salon branch.',
            },
            {
                icon: 'users',
                title: 'Staff and skills',
                text: 'Connect staff with services so customers only see available choices.',
            },
            {
                icon: 'calendar',
                title: 'Booking operations',
                text: 'Monitor schedules, queues, walk-ins, payments, and booking statuses from the provider dashboard.',
            },
        ],
        featureTitle: 'Connected with customer landing',
        featureText: 'Active provider data appears in the customer catalog so search, service details, and checkout stay consistent.',
    },
};

function RouteAction({ action, className }) {
    if (action.href) {
        return <a className={className} href={action.href}>{action.label}</a>;
    }

    return <Link className={className} to={action.to}>{action.label}</Link>;
}

function formatCouponValue(coupon) {
    const value = Number(coupon?.coupon_value || 0);

    if (coupon?.coupon_type === 'percentage') {
        return `${Math.round(value)}% off`;
    }

    return `Rp${value.toLocaleString('id-ID')} off`;
}

function formatCouponDate(date) {
    if (!date) return 'No limit';

    return new Intl.DateTimeFormat(customerDateLocale(), {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));
}

export function MenuRoutePage({ page, authUser }) {
    const content = routePages[page] || routePages.promo;
    const isPromoPage = content.className === 'promo';
    const isArticlesPage = content.className === 'articles';
    const shouldShowSecondaryAction = !(authUser && content.secondaryAction?.to === '/signup');
    const [coupons, setCoupons] = useState([]);
    const [couponsLoading, setCouponsLoading] = useState(false);
    const [couponsLoaded, setCouponsLoaded] = useState(false);
    const [couponError, setCouponError] = useState('');

    useEffect(() => {
        if (!isPromoPage) return undefined;

        let mounted = true;
        setCouponsLoading(true);
        setCouponsLoaded(false);
        setCouponError('');

        getCoupons({ per_page: 12 })
            .then((items) => {
                if (mounted) setCoupons(items);
            })
            .catch(() => {
                if (mounted) {
                    setCoupons([]);
                    setCouponError('Promos could not be loaded from the database yet.');
                }
            })
            .finally(() => {
                if (mounted) {
                    setCouponsLoading(false);
                    setCouponsLoaded(true);
                }
            });

        return () => {
            mounted = false;
        };
    }, [isPromoPage]);

    const promoStats = useMemo(() => {
        if (!isPromoPage || coupons.length === 0) return content.stats;

        const percentageValues = coupons
            .filter((coupon) => coupon.coupon_type === 'percentage')
            .map((coupon) => Number(coupon.coupon_value || 0));
        const maxPercent = percentageValues.length ? Math.max(...percentageValues) : null;
        const soonestEnd = coupons
            .map((coupon) => coupon.end_date)
            .filter(Boolean)
            .sort()[0];

        return [
            [`${coupons.length}`, 'active vouchers'],
            [maxPercent ? `${Math.round(maxPercent)}%` : 'Fixed', 'savings available'],
            [soonestEnd ? formatCouponDate(soonestEnd) : 'Active', 'nearest ending date'],
        ];
    }, [content.stats, coupons, isPromoPage]);

    const promoCards = isPromoPage
        ? coupons.map((coupon) => ({
            icon: coupon.coupon_type === 'fixed' ? 'money' : 'bookmark',
            title: coupon.code,
            value: formatCouponValue(coupon),
            text: `${coupon.product_label || 'Service promo'} - valid until ${formatCouponDate(coupon.end_date)}.`,
            remaining: coupon.remaining_quantity === null ? 'Unlimited quota' : `${coupon.remaining_quantity} quotas left`,
        }))
        : content.cards;
    const routeCards = isPromoPage ? promoCards : content.cards;

    return (
        <section className={`topbar-route-page topbar-route-page-${content.className}`}>
            <section className="topbar-route-hero">
                <div className="topbar-route-copy">
                    <span>{content.kicker}</span>
                    <h1>{content.title}</h1>
                    <p>{content.description}</p>
                    <div className="topbar-route-actions">
                        <RouteAction action={content.primaryAction} className="btn btn-primary" />
                        {shouldShowSecondaryAction && <RouteAction action={content.secondaryAction} className="btn btn-outline" />}
                    </div>
                </div>
                <div className="topbar-route-media">
                    <img src={content.image} alt={content.title} />
                </div>
            </section>

            <section className="topbar-route-stats" aria-label={`${content.kicker} summary`}>
                {promoStats.map(([value, label]) => (
                    <div key={`${value}-${label}`}>
                        <strong>{value}</strong>
                        <span>{label}</span>
                    </div>
                ))}
            </section>

            {isPromoPage && couponsLoading && (
                <section className="topbar-route-promo-state">
                    <Icon name="clock" size={22} />
                    <span>Loading active coupons from the database...</span>
                </section>
            )}

            {isPromoPage && couponError && (
                <section className="topbar-route-promo-state error">
                    <Icon name="info" size={22} />
                    <span>{couponError}</span>
                </section>
            )}

            {isPromoPage && couponsLoaded && !couponsLoading && !couponError && promoCards.length === 0 ? (
                <section className="topbar-route-promo-empty">
                    <Icon name="bookmark" size={26} />
                    <h2>No active promos yet</h2>
                    <p>Coupons activated from the admin dashboard will appear here automatically.</p>
                </section>
            ) : (
                <section className="topbar-route-card-grid">
                    {routeCards.map((card) => (
                        <article className="topbar-route-card" key={card.title}>
                            {isArticlesPage && card.image && <img src={card.image} alt={card.title} />}
                            <span><Icon name={card.icon} size={24} /></span>
                            {isArticlesPage && card.meta && <small>{card.meta}</small>}
                            <h2>{card.title}</h2>
                            {isPromoPage && card.value && <strong className="topbar-route-coupon-value">{card.value}</strong>}
                            <p>{card.text}</p>
                            {isPromoPage && card.remaining && <small>{card.remaining}</small>}
                            {isPromoPage && <Link to="/findservice">Use promo</Link>}
                            {isArticlesPage && card.to && <Link to={card.to}>Continue</Link>}
                        </article>
                    ))}
                </section>
            )}

            <section className="topbar-route-feature">
                <span><Icon name="shield" size={24} /></span>
                <div>
                    <h2>{content.featureTitle}</h2>
                    <p>{content.featureText}</p>
                </div>
            </section>
        </section>
    );
}
