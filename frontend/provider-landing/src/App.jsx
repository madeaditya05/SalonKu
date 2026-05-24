import { useEffect, useMemo, useState } from 'react';
import heroImage from './assets/provider-hero.png';
import { getCategories, registerProvider } from './api';
import { benefits, steps, testimonials } from './data/content';
import { Icon } from './components/Icons.jsx';

function normalizeUrl(url) {
    return String(url || '').replace(/\/$/, '');
}

const backendUrl = normalizeUrl(import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.1:8000');
const apiBaseUrl = normalizeUrl(import.meta.env.VITE_API_BASE_URL || `${backendUrl}/api`);
const providerLoginPath = import.meta.env.VITE_PROVIDER_LOGIN_PATH || '/provider/signin';
const providerDashboardPath = import.meta.env.VITE_PROVIDER_DASHBOARD_PATH || '/provider/dashboard';
const query = new URLSearchParams(window.location.search);

const config = {
    loginUrl: `${backendUrl}${providerLoginPath}`,
    dashboardUrl: `${backendUrl}${providerDashboardPath}`,
    registerApiUrl: `${apiBaseUrl}/auth/register/provider`,
    categoriesApiUrl: `${apiBaseUrl}/categories`,
    docsUrl: `${backendUrl}/docs/api`,
    adminLoginUrl: `${backendUrl}/admin/login`,
    openLogin: ['failed', 'open', '1'].includes(query.get('login')),
    openRegister: ['open', '1'].includes(query.get('register')),
    flash: {
        error: query.get('login_error') || '',
        success: query.get('success') || '',
    },
};

const emptyRegisterForm = {
    fullName: '',
    username: '',
    email: '',
    phone: '',
    serviceCategory: '',
    password: '',
    passwordConfirmation: '',
};

function splitName(fullName) {
    const parts = fullName.trim().split(/\s+/).filter(Boolean);
    const firstName = parts.shift() || '';
    const lastName = parts.join(' ') || firstName;

    return { firstName, lastName };
}

function usernameFromEmail(email) {
    const base = email.split('@')[0]?.replace(/[^a-zA-Z0-9_]/g, '').toLowerCase();

    return base || `mitra${Date.now().toString().slice(-5)}`;
}

function App() {
    const [categories, setCategories] = useState([]);
    const [isLoginOpen, setLoginOpen] = useState(Boolean(config.openLogin));
    const [isRegisterOpen, setRegisterOpen] = useState(Boolean(config.openRegister));
    const [registerForm, setRegisterForm] = useState(emptyRegisterForm);
    const [registerErrors, setRegisterErrors] = useState({});
    const [registerMessage, setRegisterMessage] = useState('');
    const [isRegistering, setRegistering] = useState(false);

    useEffect(() => {
        getCategories(config.categoriesApiUrl || '/api/categories')
            .then(setCategories)
            .catch(() => setCategories([]));
    }, []);

    const categoryOptions = useMemo(() => {
        if (categories.length === 0) {
            return ['Beauty & Wellness', 'Service Rumah', 'Cleaning', 'Service AC', 'Event & Catering'];
        }

        return categories.slice(0, 8).map((category) => category.name);
    }, [categories]);

    function openRegister(prefill = {}) {
        setRegisterForm((current) => ({ ...current, ...prefill }));
        setRegisterErrors({});
        setRegisterMessage('');
        setRegisterOpen(true);
    }

    function updateRegisterField(field, value) {
        setRegisterForm((current) => ({ ...current, [field]: value }));
    }

    async function submitRegister(event) {
        event.preventDefault();
        setRegistering(true);
        setRegisterErrors({});
        setRegisterMessage('');

        const { firstName, lastName } = splitName(registerForm.fullName);

        try {
            await registerProvider(config.registerApiUrl || '/api/auth/register/provider', {
                first_name: firstName,
                last_name: lastName,
                username: registerForm.username || usernameFromEmail(registerForm.email),
                email: registerForm.email,
                country_code: '+62',
                phone_number: registerForm.phone,
                service_category: registerForm.serviceCategory,
                password: registerForm.password,
                password_confirmation: registerForm.passwordConfirmation,
            });

            setRegisterForm(emptyRegisterForm);
            setRegisterMessage('Registrasi berhasil. Tunggu ACC admin, lalu login sebagai mitra.');
        } catch (error) {
            setRegisterErrors(error.errors || { form: [error.message] });
        } finally {
            setRegistering(false);
        }
    }

    return (
        <div className="provider-landing">
            <Header onLogin={() => setLoginOpen(true)} onRegister={() => openRegister()} />

            {config.flash?.error && <div className="flash-message error">{config.flash.error}</div>}
            {config.flash?.success && <div className="flash-message success">{config.flash.success}</div>}

            <main>
                <Hero onLogin={() => setLoginOpen(true)} onRegister={() => openRegister()} />
                <HowItWorks />
                <Benefits onRegister={() => openRegister()} />
                <JoinPanel
                    categories={categoryOptions}
                    onRegister={openRegister}
                />
                <Testimonials />
            </main>

            <Footer />

            <button className="chat-fab" type="button" aria-label="Bantuan chat">
                <Icon name="headset" size={22} />
            </button>

            {isLoginOpen && (
                <LoginModal
                    onClose={() => setLoginOpen(false)}
                />
            )}

            {isRegisterOpen && (
                <RegisterModal
                    form={registerForm}
                    categories={categoryOptions}
                    errors={registerErrors}
                    message={registerMessage}
                    isSubmitting={isRegistering}
                    onClose={() => setRegisterOpen(false)}
                    onChange={updateRegisterField}
                    onSubmit={submitRegister}
                />
            )}
        </div>
    );
}

function Header({ onLogin, onRegister }) {
    return (
        <header className="site-header">
            <a className="brand" href="/" aria-label="JasaKu Mitra">
                <span className="brand-mark">J</span>
                <span>
                    <strong>JasaKu Mitra</strong>
                    <small>Semua Jasa, Semua Bisa</small>
                </span>
            </a>

            <nav className="main-nav" aria-label="Navigasi utama">
                <a href="#home">Beranda</a>
                <a href="#cara-kerja">Cara Kerja</a>
                <a href="#keuntungan">Keuntungan</a>
                <a href="#daftar">Daftar</a>
                <a href="#testimoni">Testimoni</a>
            </nav>

            <div className="header-actions">
                <button className="btn ghost" type="button" onClick={onLogin}>Login Mitra</button>
                <button className="btn solid" type="button" onClick={onRegister}>Daftar Mitra</button>
            </div>
        </header>
    );
}

function Hero({ onRegister }) {
    return (
        <section className="hero-section" id="home">
            <div className="hero-copy">
                <p className="eyebrow">Platform mitra jasa terpercaya</p>
                <h1>
                    Bergabung Jadi Mitra JasaKu
                    <span>Kembangkan Bisnismu,</span>
                    Jangkau Lebih Banyak Pelanggan
                </h1>
                <span className="hero-underline" />
                <p className="hero-description">
                    Kelola layanan, jadwal, booking, cabang, dan staff dari satu dashboard Laravel yang rapi setelah akunmu aktif.
                </p>

                <div className="hero-proof">
                    <ProofItem icon="users" label="Jangkauan Lebih Luas" />
                    <ProofItem icon="calendar" label="Booking Otomatis 24/7" />
                    <ProofItem icon="shield" label="Pembayaran Aman" />
                    <ProofItem icon="headset" label="Dukungan Tim" />
                </div>

                <button className="btn hero-cta" type="button" onClick={onRegister}>
                    Daftar Sekarang, Gratis!
                </button>

                <p className="small-note">
                    <Icon name="shield" size={16} /> Gratis bergabung dan tanpa biaya tersembunyi.
                </p>
            </div>

            <div className="hero-visual" aria-label="Ilustrasi mitra JasaKu">
                <img src={heroImage} alt="Mitra bisnis jasa profesional" />
                <div className="stat-card bookings">
                    <span>Total Booking</span>
                    <strong>+2.356</strong>
                    <small>+32% dari bulan lalu</small>
                </div>
                <div className="stat-card income">
                    <span>Pendapatan Bulan Ini</span>
                    <strong>Rp12.750.000</strong>
                    <svg viewBox="0 0 160 56" aria-hidden="true">
                        <path d="M4 45C25 16 41 49 61 23s31 4 47-8 31 5 48-11" />
                    </svg>
                </div>
                <div className="rating-card">
                    <div className="avatar-row">
                        <span></span><span></span><span></span><span></span>
                    </div>
                    <Stars />
                    <small>4.8 dari ulasan mitra</small>
                </div>
            </div>
        </section>
    );
}

function Stars({ rating }) {
    return (
        <span className="stars" aria-label="5 stars">
            <span className="star-icons">
                {Array.from({ length: 5 }).map((_, index) => (
                    <Icon name="star" size={15} key={index} />
                ))}
            </span>
            {rating && <span>{rating}</span>}
        </span>
    );
}

function ProofItem({ icon, label }) {
    return (
        <div className="proof-item">
            <Icon name={icon} size={18} />
            <span>{label}</span>
        </div>
    );
}

function HowItWorks() {
    return (
        <section className="steps-section" id="cara-kerja">
            <h2>Cara Bergabung Menjadi Mitra</h2>
            <div className="steps-grid">
                {steps.map(([title, description], index) => (
                    <article className="step-card" key={title}>
                        <div className="step-icon">
                            <Icon name={index === 2 ? 'store' : index === 3 ? 'calendar' : index === 4 ? 'chart' : 'file'} />
                        </div>
                        <span className="step-number">{index + 1}</span>
                        <h3>{title}</h3>
                        <p>{description}</p>
                        {index < steps.length - 1 && <Icon name="arrow" className="step-arrow" size={28} />}
                    </article>
                ))}
            </div>
        </section>
    );
}

function Benefits({ onRegister }) {
    return (
        <section className="benefits-section" id="keuntungan">
            <div className="benefit-intro">
                <h2>Keuntungan Menjadi Mitra JasaKu</h2>
                <p>Dapatkan semua yang kamu butuhkan untuk mengembangkan bisnis jasa secara maksimal.</p>
                <button className="btn solid small" type="button" onClick={onRegister}>Daftar Sekarang</button>
            </div>

            <div className="benefits-grid">
                {benefits.map((benefit) => (
                    <article className="benefit-item" key={benefit.title}>
                        <div className="benefit-icon">
                            <Icon name={benefit.icon} />
                        </div>
                        <div>
                            <h3>{benefit.title}</h3>
                            <p>{benefit.description}</p>
                        </div>
                    </article>
                ))}
            </div>
        </section>
    );
}

function JoinPanel({ categories, onRegister }) {
    const [lead, setLead] = useState({
        fullName: '',
        phone: '',
        email: '',
        serviceCategory: categories[0] || '',
    });

    useEffect(() => {
        setLead((current) => ({
            ...current,
            serviceCategory: current.serviceCategory || categories[0] || '',
        }));
    }, [categories]);

    return (
        <section className="join-panel" id="daftar">
            <div className="phone-preview" aria-hidden="true">
                <div className="phone-shell">
                    <div className="phone-top"></div>
                    <div className="phone-greeting">Halo, Mitra!</div>
                    <div className="mini-grid">
                        <span></span><span></span><span></span><span></span>
                    </div>
                    <div className="mini-card"></div>
                    <div className="mini-button"></div>
                </div>
            </div>

            <div className="join-copy">
                <h2>Siap Mengembangkan Bisnismu Bersama JasaKu?</h2>
                <p>Daftarkan bisnismu sekarang dan mulai terima booking dari pelanggan baru setelah akunmu aktif.</p>
                <ul>
                    <li><Icon name="check" size={15} /> Proses pendaftaran cepat dan mudah</li>
                    <li><Icon name="check" size={15} /> Gratis bergabung, tanpa biaya bulanan</li>
                    <li><Icon name="check" size={15} /> Tim kami siap membantu kamu sukses</li>
                </ul>
            </div>

            <form className="lead-form" onSubmit={(event) => {
                event.preventDefault();
                onRegister(lead);
            }}>
                <label>
                    Nama Lengkap
                    <input
                        value={lead.fullName}
                        onChange={(event) => setLead({ ...lead, fullName: event.target.value })}
                        placeholder="Masukkan nama lengkap"
                    />
                </label>
                <label>
                    Nomor WhatsApp
                    <input
                        value={lead.phone}
                        onChange={(event) => setLead({ ...lead, phone: event.target.value })}
                        placeholder="0812-3456-7890"
                    />
                </label>
                <label>
                    Email
                    <input
                        type="email"
                        value={lead.email}
                        onChange={(event) => setLead({ ...lead, email: event.target.value })}
                        placeholder="nama@email.com"
                    />
                </label>
                <label>
                    Jenis Layanan
                    <select
                        value={lead.serviceCategory}
                        onChange={(event) => setLead({ ...lead, serviceCategory: event.target.value })}
                    >
                        {categories.map((category) => <option key={category}>{category}</option>)}
                    </select>
                </label>
                <button className="btn solid form-submit" type="submit">Daftar Menjadi Mitra</button>
                <p>Dengan mendaftar, kamu menyetujui Syarat & Ketentuan JasaKu.</p>
            </form>
        </section>
    );
}

function Testimonials() {
    return (
        <section className="testimonials-section" id="testimoni">
            <h2>Mitra Kami, Cerita Mereka</h2>
            <div className="testimonial-grid">
                {testimonials.map((item) => (
                    <article className="testimonial-card" key={item.name}>
                        <div className="testimonial-head">
                            <span className="testimonial-avatar">{item.name.charAt(0)}</span>
                            <div>
                                <h3>{item.name}</h3>
                                <p>{item.role}</p>
                            </div>
                        </div>
                        <blockquote>{item.quote}</blockquote>
                        <Stars rating={item.rating} />
                    </article>
                ))}
            </div>
            <p className="testimonial-note">Bergabung dengan mitra lainnya yang telah merasakan manfaat JasaKu.</p>
        </section>
    );
}

function Footer() {
    return (
        <footer className="site-footer">
            <div>
                <a className="brand" href="/" aria-label="JasaKu Mitra">
                    <span className="brand-mark">J</span>
                    <span>
                        <strong>JasaKu Mitra</strong>
                        <small>Semua Jasa, Semua Bisa</small>
                    </span>
                </a>
                <p>Platform untuk membantu penyedia jasa mengelola layanan dan mengembangkan bisnis.</p>
            </div>
            <div>
                <h3>Perusahaan</h3>
                <a href="#keuntungan">Tentang Kami</a>
                <a href="#cara-kerja">Cara Kerja</a>
                <a href="#testimoni">Cerita Mitra</a>
            </div>
            <div>
                <h3>Layanan</h3>
                <a href="#daftar">Untuk Mitra</a>
                <a href={config.docsUrl}>Dokumentasi API</a>
                <a href={config.adminLoginUrl}>Admin</a>
            </div>
            <div>
                <h3>Bantuan</h3>
                <a href="#home">Pusat Bantuan</a>
                <a href="#daftar">Kontak</a>
                <a href="#home">FAQ</a>
            </div>
        </footer>
    );
}

function LoginModal({ onClose }) {
    return (
        <Modal title="Login Mitra" onClose={onClose}>
            <form className="modal-form" action={config.loginUrl} method="POST">
                <label>
                    Email
                    <input name="login_email" type="email" required />
                </label>
                <label>
                    Password
                    <input name="login_password" type="password" required />
                </label>
                <label className="check-row">
                    <input type="checkbox" name="remember" value="1" />
                    Ingat saya
                </label>
                <button className="btn solid form-submit" type="submit">Masuk ke Dashboard</button>
                <p className="modal-hint">Setelah login berhasil, kamu akan diarahkan ke dashboard provider Laravel.</p>
            </form>
        </Modal>
    );
}

function RegisterModal({ form, categories, errors, message, isSubmitting, onClose, onChange, onSubmit }) {
    return (
        <Modal title="Daftar Mitra" onClose={onClose}>
            <form className="modal-form two-column" onSubmit={onSubmit}>
                {errors.form?.[0] && <div className="form-alert">{errors.form[0]}</div>}
                {message && <div className="form-alert success">{message}</div>}
                <label>
                    Nama Lengkap
                    <input value={form.fullName} onChange={(event) => onChange('fullName', event.target.value)} required />
                    {errors.first_name?.[0] && <span className="field-error">{errors.first_name[0]}</span>}
                </label>
                <label>
                    Username
                    <input value={form.username} onChange={(event) => onChange('username', event.target.value)} placeholder="boleh dikosongkan" />
                    {errors.username?.[0] && <span className="field-error">{errors.username[0]}</span>}
                </label>
                <label>
                    Email
                    <input type="email" value={form.email} onChange={(event) => onChange('email', event.target.value)} required />
                    {errors.email?.[0] && <span className="field-error">{errors.email[0]}</span>}
                </label>
                <label>
                    Nomor WhatsApp
                    <input value={form.phone} onChange={(event) => onChange('phone', event.target.value)} required />
                    {errors.phone_number?.[0] && <span className="field-error">{errors.phone_number[0]}</span>}
                </label>
                <label>
                    Jenis Layanan
                    <select value={form.serviceCategory} onChange={(event) => onChange('serviceCategory', event.target.value)}>
                        <option value="">Pilih kategori layanan</option>
                        {categories.map((category) => <option key={category}>{category}</option>)}
                    </select>
                </label>
                <label>
                    Password
                    <input type="password" value={form.password} onChange={(event) => onChange('password', event.target.value)} required />
                    {errors.password?.[0] && <span className="field-error">{errors.password[0]}</span>}
                </label>
                <label>
                    Konfirmasi Password
                    <input type="password" value={form.passwordConfirmation} onChange={(event) => onChange('passwordConfirmation', event.target.value)} required />
                </label>
                <button className="btn solid form-submit wide" type="submit" disabled={isSubmitting}>
                    {isSubmitting ? 'Mendaftarkan...' : 'Daftar Menjadi Mitra'}
                </button>
            </form>
        </Modal>
    );
}

function Modal({ title, children, onClose }) {
    return (
        <div className="modal-backdrop" role="dialog" aria-modal="true" aria-label={title}>
            <div className="modal-card">
                <div className="modal-header">
                    <h2>{title}</h2>
                    <button type="button" onClick={onClose} aria-label="Tutup">
                        <Icon name="close" size={20} />
                    </button>
                </div>
                {children}
            </div>
        </div>
    );
}

export default App;
