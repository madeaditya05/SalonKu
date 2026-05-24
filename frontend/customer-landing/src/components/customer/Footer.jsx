import { Link } from 'react-router-dom';
import { Icon } from '../Icons.jsx';

export function CustomerFooter({ branchCount = 0 }) {
    const catalogLabel = branchCount > 0 ? `${branchCount} active salon branches` : 'Active salon branches';

    return (
        <footer className="booking-footer">
            <div className="booking-footer-grid">
                <div>
                    <Link className="booking-brand footer-brand" to="/" aria-label="JasaKu customer home">
                        <span className="booking-brand-mark"><Icon name="beauty" size={25} /></span>
                        <strong>JasaKu</strong>
                    </Link>
                    <p>Book trusted salons, compare services, choose staff, and manage every visit from one customer account.</p>
                    <span><Icon name="store" size={16} /> {catalogLabel}</span>
                    <span><Icon name="mail" size={16} /> support@jasaku.id</span>
                </div>
                <div>
                    <h2>Customer</h2>
                    <Link to="/">Home</Link>
                    <Link to="/findservice">Find Services</Link>
                    <Link to="/promo">Promos</Link>
                    <Link to="/articles">Articles</Link>
                </div>
                <div>
                    <h2>Account</h2>
                    <Link to="/signup">Create Account</Link>
                    <Link to="/signin">Sign In</Link>
                    <Link to="/my-bookings">My Bookings</Link>
                    <Link to="/promo">Voucher & Deals</Link>
                </div>
                <div>
                    <h2>Popular Cities</h2>
                    <a>Jakarta</a>
                    <a>Bandung</a>
                    <a>Surabaya</a>
                    <a>Yogyakarta</a>
                    <a>Bali</a>
                </div>
                <div>
                    <h2>Services</h2>
                    <a><Icon name="beauty" size={16} /> Hair & Beauty</a>
                    <a><Icon name="spa" size={16} /> Spa & Massage</a>
                    <a><Icon name="heart" size={16} /> Nails & Care</a>
                    <a><Icon name="calendar" size={16} /> Queue & Schedule</a>
                </div>
            </div>
            <div className="booking-top-links">
                <h2>Quick Access</h2>
                <p>Salon booking Haircut Hair spa Facial Manicure Pedicure Massage Promo vouchers Pay at salon Scheduled booking Queue booking Staff selection Branch details Customer support Provider portal</p>
            </div>
            <div className="booking-payment-row">
                <div>
                    <h2>Payment Options</h2>
                    <span>Pay at Salon</span>
                    <span>Down Payment</span>
                    <span>Full Payment</span>
                    <span>Voucher Discount</span>
                </div>
                <div>
                    <h2>Need Help?</h2>
                    <span>Help Center</span>
                    <span>Booking Status</span>
                    <span>Payment Policy</span>
                    <span>Contact Support</span>
                </div>
            </div>
            <div className="booking-footer-bottom">
                <p>Copyright 2026 JasaKu. All rights reserved.</p>
                <span>Privacy Policy</span>
                <span>Terms of Service</span>
                <span>Refund Policy</span>
            </div>
        </footer>
    );
}
