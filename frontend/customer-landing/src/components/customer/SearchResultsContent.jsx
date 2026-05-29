import { useEffect, useMemo, useState } from 'react';
import { Icon } from '../Icons.jsx';

const heroImage = 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=1800&q=88';
const resultsPerPage = 4;
const allFilterOption = 'All';
const emptyFilters = {
    type: [],
    price: [],
    service: [],
    customerRating: '',
    starRating: '',
    facility: [],
};

const priceRanges = {
    'Up to Rp100K': [0, 100000],
    'Rp100K - Rp250K': [100000, 250000],
    'Rp250K - Rp500K': [250000, 500000],
    'Rp500K+': [500000, Infinity],
};

function todayInputValue() {
    const today = new Date();
    today.setMinutes(today.getMinutes() - today.getTimezoneOffset());

    return today.toISOString().slice(0, 10);
}

function displayPrice(value) {
    return `Rp${Number(value || 0).toLocaleString('id-ID')}`;
}

function branchPrice(branch) {
    return Number(branch.minPrice || 0);
}

function formatDistance(value) {
    const distance = Number(value);

    if (!Number.isFinite(distance)) return '';

    return `${distance.toLocaleString('en-US', { maximumFractionDigits: 1 })} km from your location`;
}

function salonFromBranch(branch) {
    const price = branchPrice(branch);
    const servicesCount = Number(branch.servicesCount || 0);
    const staffCount = Number(branch.staffCount || 0);
    const workingTime = [branch.workingStart, branch.workingEnd].filter(Boolean).join(' - ');
    const serviceCategories = Array.isArray(branch.serviceCategories) ? branch.serviceCategories.filter(Boolean) : [];
    const serviceTitles = Array.isArray(branch.serviceTitles) ? branch.serviceTitles.filter(Boolean) : [];
    const images = Array.isArray(branch.galleryImages) && branch.galleryImages.length > 0
        ? branch.galleryImages
        : [branch.image].filter(Boolean);

    return {
        id: branch.id,
        name: branch.name || 'Salon Branch',
        address: branch.address || branch.locationLabel || 'Salon address is not available',
        image: branch.image,
        images,
        price,
        rating: Number(branch.rating || 4.8),
        badge: servicesCount > 0 ? `${servicesCount} Services` : 'Salon',
        staffCount,
        servicesCount,
        serviceCategories,
        serviceTitles,
        hasQueueService: Boolean(branch.hasQueueService),
        hasScheduledService: Boolean(branch.hasScheduledService),
        supportsPayAtSalon: Boolean(branch.supportsPayAtSalon),
        meta: [
            servicesCount > 0 ? `${servicesCount} active services` : 'Services are available in the details',
            staffCount > 0 ? `${staffCount} staff` : 'Staff follows the salon schedule',
            workingTime || 'Operating hours follow the salon schedule',
        ],
        benefits: [
            formatDistance(branch.distanceKm),
            servicesCount > 0 ? 'Choose services from the salon catalog' : 'Service details are available after choosing a salon',
            staffCount > 0 ? 'Choose staff during booking' : 'Staff availability follows the schedule',
        ].filter(Boolean),
        sourceBranch: branch,
    };
}

function RatingStars({ value = 4.8 }) {
    return (
        <div className="findservice-stars" aria-label={`${value} rating salon`}>
            {Array.from({ length: 5 }).map((_, index) => (
                <Icon key={index} name="star" size={20} className={index + 1 <= Math.round(value) ? 'is-filled' : ''} />
            ))}
        </div>
    );
}

function serviceOptionsFromSalons(salons) {
    const options = salons.flatMap((salon) => salon.serviceCategories).filter(Boolean);
    const uniqueOptions = Array.from(new Set(options));

    return uniqueOptions.length > 0 ? uniqueOptions : ['Hair', 'Face & Spa', 'Nail'];
}

function buildFilterGroups(salons) {
    return [
        {
            id: 'type',
            title: 'Salon Type',
            icon: 'store',
            options: [allFilterOption, 'Hair Salon', 'Beauty Studio', 'Nail Studio', 'Spa', 'Barber'],
            mode: 'multi',
            more: true,
        },
        {
            id: 'price',
            title: 'Price Range',
            icon: 'money',
            options: Object.keys(priceRanges),
            mode: 'multi',
        },
        {
            id: 'service',
            title: 'Popular Services',
            icon: 'beauty',
            options: serviceOptionsFromSalons(salons),
            mode: 'multi',
        },
        {
            id: 'customerRating',
            title: 'Customer Rating',
            icon: 'star',
            pills: ['3+', '3.5+', '4+', '4.5+'],
            mode: 'single',
        },
        {
            id: 'starRating',
            title: 'Star Rating',
            icon: 'star',
            pills: ['1', '2', '3', '4', '5'],
            mode: 'single',
            star: true,
        },
        {
            id: 'facility',
            title: 'Facilities',
            icon: 'shield',
            options: [allFilterOption, 'Scheduled Booking', 'Queue', 'Choose Staff', 'Pay at Salon'],
            mode: 'multi',
            more: true,
        },
    ];
}

function groupValues(filters, groupId) {
    const value = filters[groupId];

    return Array.isArray(value) ? value : [];
}

function optionIsChecked(filters, group, option) {
    const values = groupValues(filters, group.id);

    if (option === allFilterOption) {
        return values.length === 0;
    }

    return values.includes(option);
}

function pillIsActive(filters, group, pill) {
    return filters[group.id] === pill;
}

function selectedFilterCount(filters) {
    return Object.values(filters).reduce((total, value) => {
        if (Array.isArray(value)) return total + value.length;

        return value ? total + 1 : total;
    }, 0);
}

function selectedGroupCount(filters, group) {
    if (group.mode === 'single') return filters[group.id] ? 1 : 0;

    return groupValues(filters, group.id).length;
}

function FilterGroup({ group, filters, onToggle }) {
    const selectedCount = selectedGroupCount(filters, group);

    return (
        <section className="findservice-filter-group">
            <h2>
                <span><Icon name={group.icon || 'filter'} size={16} /></span>
                {group.title}
                {selectedCount > 0 && <b>{selectedCount}</b>}
            </h2>
            {group.options && (
                <div className="findservice-check-list">
                    {group.options.map((option) => {
                        const checked = optionIsChecked(filters, group, option);

                        return (
                            <label className={checked ? 'is-checked' : ''} key={option}>
                                <input
                                    type="checkbox"
                                    checked={checked}
                                    onChange={() => onToggle(group, option)}
                                />
                                <span>{option}</span>
                                {checked && <Icon name="check" size={15} />}
                            </label>
                        );
                    })}
                </div>
            )}
            {group.pills && (
                <div className="findservice-pill-list">
                    {group.pills.map((pill) => {
                        const active = pillIsActive(filters, group, pill);

                        return (
                            <button
                                className={active ? 'active' : ''}
                                type="button"
                                aria-pressed={active}
                                key={pill}
                                onClick={() => onToggle(group, pill)}
                            >
                                <span>{pill}</span>
                                <Icon name="star" size={14} />
                            </button>
                        );
                    })}
                </div>
            )}
            {group.more && (
                <button className="findservice-see-more" type="button" aria-label={`View more options for ${group.title}`}>
                    View more <Icon name="chevron" size={17} />
                </button>
            )}
        </section>
    );
}

function SalonCard({ salon, selectedBranch, chooseBranch }) {
    const isSelected = selectedBranch?.id === salon.sourceBranch?.id;
    const canChoose = Boolean(salon.sourceBranch);
    const images = salon.images.length > 0 ? salon.images : [salon.image].filter(Boolean);
    const [activeImageIndex, setActiveImageIndex] = useState(0);
    const [isMediaActive, setMediaActive] = useState(false);
    const canSlide = images.length > 1;

    useEffect(() => {
        if (!canSlide || !isMediaActive) return undefined;

        const interval = window.setInterval(() => {
            setActiveImageIndex((current) => (current + 1) % images.length);
        }, 1800);

        return () => window.clearInterval(interval);
    }, [canSlide, images.length, isMediaActive]);

    useEffect(() => {
        if (activeImageIndex >= images.length) {
            setActiveImageIndex(0);
        }
    }, [activeImageIndex, images.length]);

    function showPreviousImage(event) {
        event.stopPropagation();
        setActiveImageIndex((current) => (current - 1 + images.length) % images.length);
    }

    function showNextImage(event) {
        event.stopPropagation();
        setActiveImageIndex((current) => (current + 1) % images.length);
    }

    return (
        <article className={`findservice-hotel-card${isSelected ? ' is-selected' : ''}`}>
            <div
                className="findservice-card-media"
                onMouseEnter={() => setMediaActive(true)}
                onMouseLeave={() => setMediaActive(false)}
                onFocus={() => setMediaActive(true)}
                onBlur={() => setMediaActive(false)}
                tabIndex={0}
            >
                <span>{salon.badge}</span>
                <div className="findservice-slide-track" style={{ transform: `translateX(-${activeImageIndex * 100}%)` }}>
                    {images.map((image, index) => (
                        <div className="findservice-slide" key={`${salon.id}-${image}-${index}`}>
                            <img src={image} alt={`${salon.name} ${index + 1}`} loading="lazy" />
                        </div>
                    ))}
                </div>
                {canSlide && (
                    <>
                        <button className="findservice-slider-button is-prev" type="button" aria-label="Previous image" onClick={showPreviousImage}>
                            <Icon name="arrow" size={21} />
                        </button>
                        <button className="findservice-slider-button is-next" type="button" aria-label="Next image" onClick={showNextImage}>
                            <Icon name="arrow" size={21} />
                        </button>
                        <div className="findservice-slide-dots" aria-hidden="true">
                            {images.map((image, index) => (
                                <span className={activeImageIndex === index ? 'active' : ''} key={`${image}-${index}`} />
                            ))}
                        </div>
                    </>
                )}
            </div>

            <div className="findservice-card-body">
                <div className="findservice-card-actions" aria-label="Salon actions">
                    <button type="button" aria-label="Save salon"><Icon name="heart" size={18} /></button>
                    <button type="button" aria-label="Share salon"><Icon name="more" size={18} /></button>
                </div>

                <RatingStars value={salon.rating} />
                <h2>{salon.name}</h2>
                <p className="findservice-location"><Icon name="pin" size={18} /> {salon.address}</p>
                <p className="findservice-amenities">
                    {salon.meta.map((item, index) => (
                        <FragmentText text={item} withDot={index < salon.meta.length - 1} key={item} />
                    ))}
                </p>

                <div className="findservice-benefits">
                    {salon.benefits.slice(0, 2).map((benefit) => (
                        <p className="is-good" key={benefit}><Icon name="check" size={16} /> {benefit}</p>
                    ))}
                </div>

                <div className="findservice-card-footer">
                    <p>
                        {salon.price > 0 ? (
                            <>
                                <span>From</span>
                                <strong>{displayPrice(salon.price)}</strong>
                            </>
                        ) : (
                            <strong>Price details</strong>
                        )}
                    </p>
                    <button
                        className="findservice-select-button"
                        type="button"
                        aria-disabled={!canChoose}
                        onClick={() => {
                            if (canChoose) chooseBranch(salon.sourceBranch);
                        }}
                    >
                        View Services <Icon name="chevron" size={16} />
                    </button>
                </div>
            </div>
        </article>
    );
}

function FragmentText({ text, withDot }) {
    return (
        <>
            {text}
            {withDot && <span />}
        </>
    );
}

function normalizedText(values) {
    return values.filter(Boolean).join(' ').toLowerCase();
}

function salonTypeTags(salon) {
    const text = normalizedText([salon.name, ...salon.serviceCategories, ...salon.serviceTitles]);
    const tags = new Set(['Beauty Studio']);

    if (text.includes('hair') || text.includes('salon') || text.includes('cream') || text.includes('color')) {
        tags.add('Hair Salon');
    }

    if (text.includes('nail') || text.includes('manicure') || text.includes('pedicure')) {
        tags.add('Nail Studio');
    }

    if (text.includes('spa') || text.includes('face') || text.includes('facial')) {
        tags.add('Spa');
    }

    if (text.includes('barber') || text.includes('pria')) {
        tags.add('Barber');
    }

    return Array.from(tags);
}

function matchesPriceRange(salon, rangeName) {
    const [min, max] = priceRanges[rangeName] || [];
    const price = Number(salon.price || 0);

    if (!price || min === undefined) return false;
    if (max === Infinity) return price >= min;

    return price >= min && price <= max;
}

function matchesFacility(salon, facility) {
    const facilityChecks = {
        'Scheduled Booking': () => salon.hasScheduledService,
        Queue: () => salon.hasQueueService,
        'Choose Staff': () => salon.staffCount > 0,
        'Pay at Salon': () => salon.supportsPayAtSalon,
    };

    return facilityChecks[facility]?.() ?? false;
}

function matchesSalonFilters(salon, filters) {
    const typeFilters = groupValues(filters, 'type');
    const priceFilters = groupValues(filters, 'price');
    const serviceFilters = groupValues(filters, 'service');
    const facilityFilters = groupValues(filters, 'facility');
    const ratingFilter = filters.customerRating;
    const starFilter = filters.starRating;

    if (typeFilters.length > 0 && !typeFilters.some((type) => salonTypeTags(salon).includes(type))) {
        return false;
    }

    if (priceFilters.length > 0 && !priceFilters.some((range) => matchesPriceRange(salon, range))) {
        return false;
    }

    if (serviceFilters.length > 0 && !serviceFilters.some((service) => salon.serviceCategories.includes(service) || salon.serviceTitles.includes(service))) {
        return false;
    }

    if (ratingFilter && Number(salon.rating || 0) < Number(String(ratingFilter).replace('+', ''))) {
        return false;
    }

    if (starFilter && Math.round(Number(salon.rating || 0)) < Number(starFilter)) {
        return false;
    }

    if (facilityFilters.length > 0 && !facilityFilters.every((facility) => matchesFacility(salon, facility))) {
        return false;
    }

    return true;
}

function createPageItems(currentPage, totalPages) {
    if (totalPages <= 5) {
        return Array.from({ length: totalPages }, (_, index) => index + 1);
    }

    if (currentPage <= 3) {
        return [1, 2, 3, 'ellipsis', totalPages];
    }

    if (currentPage >= totalPages - 2) {
        return [1, 'ellipsis', totalPages - 2, totalPages - 1, totalPages];
    }

    return [1, 'ellipsis-start', currentPage, 'ellipsis-end', totalPages];
}

export function SearchResultsContent({
    branches = [],
    isLoading,
    selectedLocation,
    selectedBranch,
    chooseBranch,
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
    const [viewMode, setViewMode] = useState('list');
    const [currentPage, setCurrentPage] = useState(1);
    const [draftFilters, setDraftFilters] = useState(emptyFilters);
    const [appliedFilters, setAppliedFilters] = useState(emptyFilters);
    const [isLocating, setLocating] = useState(false);
    const locationLabel = selectedLocation?.label || locationQuery || 'all locations';
    const minBookingDate = todayInputValue();
    const formBookingDate = bookingDate || minBookingDate;
    const activeFilterCount = selectedFilterCount(draftFilters);

    const salons = useMemo(() => (
        [...branches]
            .sort((left, right) => Number(right.rating || 0) - Number(left.rating || 0))
            .map(salonFromBranch)
    ), [branches]);
    const filterGroups = useMemo(() => buildFilterGroups(salons), [salons]);
    const filteredSalons = useMemo(() => (
        salons.filter((salon) => matchesSalonFilters(salon, appliedFilters))
    ), [appliedFilters, salons]);

    const totalPages = Math.max(1, Math.ceil(filteredSalons.length / resultsPerPage));
    const pageItems = useMemo(() => createPageItems(currentPage, totalPages), [currentPage, totalPages]);
    const paginatedSalons = useMemo(() => {
        const start = (currentPage - 1) * resultsPerPage;
        return filteredSalons.slice(start, start + resultsPerPage);
    }, [currentPage, filteredSalons]);

    useEffect(() => {
        setCurrentPage(1);
    }, [filteredSalons.length, locationLabel]);

    useEffect(() => {
        if (currentPage > totalPages) {
            setCurrentPage(totalPages);
        }
    }, [currentPage, totalPages]);

    async function useCurrentPosition() {
        setLocating(true);

        try {
            await useCurrentLocation({ refreshResults: true });
        } finally {
            setLocating(false);
        }
    }

    function toggleFilter(group, option) {
        setDraftFilters((current) => {
            if (group.mode === 'single') {
                return {
                    ...current,
                    [group.id]: current[group.id] === option ? '' : option,
                };
            }

            if (option === allFilterOption) {
                return { ...current, [group.id]: [] };
            }

            const values = groupValues(current, group.id);
            const nextValues = values.includes(option)
                ? values.filter((value) => value !== option)
                : [...values, option];

            return { ...current, [group.id]: nextValues };
        });
    }

    function applyFilters() {
        setAppliedFilters(draftFilters);
        setCurrentPage(1);
    }

    function clearFilters() {
        setDraftFilters(emptyFilters);
        setAppliedFilters(emptyFilters);
        setCurrentPage(1);
    }

    return (
        <section className="findservice-page" id="top">
            <section className="findservice-hero">
                <img src={heroImage} alt="Interior salon modern" />
                <div className="findservice-hero-overlay" />
                <h1>{filteredSalons.length} salons in {locationLabel}</h1>

                <form className="findservice-search-panel" onSubmit={submitLocation}>
                    <label>
                        <Icon name="pin" size={52} />
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
                    <label>
                        <Icon name="calendar" size={52} />
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
                        <Icon name="search" size={35} />
                    </button>
                    {searchError && <p className="search-date-error" role="alert">{searchError}</p>}
                </form>
            </section>

            <section className="findservice-toolbar" aria-label="View mode">
                <div className="findservice-view-toggle">
                    <button className={viewMode === 'list' ? 'active' : ''} type="button" onClick={() => setViewMode('list')} aria-label="List view">
                        <Icon name="list" size={18} />
                    </button>
                    <button className={viewMode === 'grid' ? 'active' : ''} type="button" onClick={() => setViewMode('grid')} aria-label="Grid view">
                        <Icon name="grid" size={18} />
                    </button>
                </div>
            </section>

            <section className={`findservice-results-layout is-${viewMode}`}>
                {viewMode === 'list' && (
                    <aside className="findservice-filter-panel">
                        <div className="findservice-filter-head">
                            <div>
                                <span><Icon name="filter" size={18} /></span>
                                <strong>Filter</strong>
                            </div>
                            <small>{activeFilterCount > 0 ? `${activeFilterCount} aktif` : 'Optional'}</small>
                        </div>
                        {filterGroups.map((group) => (
                            <FilterGroup
                                group={group}
                                filters={draftFilters}
                                onToggle={toggleFilter}
                                key={group.title}
                            />
                        ))}
                        <div className="findservice-filter-actions">
                            <button type="button" onClick={clearFilters}>
                                <Icon name="x" size={16} />
                                Clear
                            </button>
                            <button type="button" onClick={applyFilters}>
                                <Icon name="check" size={16} />
                                Apply
                            </button>
                        </div>
                    </aside>
                )}

                <div className={`findservice-hotel-list is-${viewMode}`}>
                    {isLoading ? (
                        <div className="findservice-empty">Loading salons from API...</div>
                    ) : paginatedSalons.length > 0 ? (
                        paginatedSalons.map((salon) => (
                            <SalonCard
                                salon={salon}
                                selectedBranch={selectedBranch}
                                chooseBranch={chooseBranch}
                                key={salon.id}
                            />
                        ))
                    ) : (
                        <div className="findservice-empty">No salons match your filters or search. Try changing the filters.</div>
                    )}

                    {totalPages > 1 && (
                        <nav className="findservice-pagination" aria-label="Pagination">
                            <button
                                type="button"
                                aria-label="Previous page"
                                aria-disabled={currentPage === 1}
                                onClick={() => setCurrentPage((page) => Math.max(1, page - 1))}
                            >
                                <Icon name="chevron" size={18} />
                            </button>
                            {pageItems.map((item) => (
                                typeof item === 'number' ? (
                                    <button
                                        className={currentPage === item ? 'active' : ''}
                                        type="button"
                                        key={item}
                                        onClick={() => setCurrentPage(item)}
                                    >
                                        {item}
                                    </button>
                                ) : (
                                    <span key={item}>..</span>
                                )
                            ))}
                            <button
                                type="button"
                                aria-label="Next page"
                                aria-disabled={currentPage === totalPages}
                                onClick={() => setCurrentPage((page) => Math.min(totalPages, page + 1))}
                            >
                                <Icon name="chevron" size={18} />
                            </button>
                        </nav>
                    )}
                </div>
            </section>

            <a className="findservice-back-top" href="#top" aria-label="Back to top">
                <Icon name="arrow" size={26} />
            </a>
        </section>
    );
}
