import { useState } from 'react';
import { Link, Navigate } from 'react-router-dom';
import { Icon } from '../Icons.jsx';

function AuthIllustration() {
    return (
        <svg className="booking-auth-illustration-svg" viewBox="0 0 620 560" role="img" aria-label="Secure account illustration">
            <path d="M96 475h430" stroke="#425963" strokeWidth="3" strokeLinecap="round" />
            <path d="M207 167h229a35 35 0 0 1 35 35v273H172V202a35 35 0 0 1 35-35Z" fill="#425963" />
            <path d="M213 181h72c9 0 10 17 21 17h55c12 0 10-17 21-17h49a28 28 0 0 1 28 28v266H190V209a28 28 0 0 1 23-28Z" fill="#fff" />
            <path d="M395 110a48 48 0 0 1 94 18l12 62-111 31-18-65c-7-25 2-39 23-46Z" fill="#c8d0d1" stroke="#9aa6a8" strokeWidth="3" />
            <path d="M414 151a23 23 0 0 1 45-9l9 42-45 13-10-40c-.5-2-1-4-1-6Z" fill="#fff" stroke="#64757a" strokeWidth="3" />
            <rect x="286" y="390" width="137" height="43" rx="4" fill="#9b96eb" />
            <rect x="288" y="258" width="166" height="27" rx="4" fill="#fff" stroke="#e2e2e8" />
            <rect x="288" y="329" width="166" height="27" rx="4" fill="#fff" stroke="#e2e2e8" />
            <path d="M300 272h69M300 343h92M301 238h42M300 310h42M300 382h42" stroke="#9b96eb" strokeWidth="8" />
            <path d="M315 235h74" stroke="#c4c0f3" strokeWidth="10" />
            <circle cx="352" cy="221" r="10" fill="#5947d7" />
            <path d="M335 246c4-18 32-18 36 0Z" fill="#5947d7" />
            <path d="M304 365h8M322 365h8M340 365h8M358 365h8M376 365h8" stroke="#9b96eb" strokeWidth="8" strokeLinecap="round" />
            <rect x="460" y="280" width="58" height="54" rx="13" fill="#b6b0f0" />
            <circle cx="489" cy="306" r="18" fill="#5947d7" />
            <path d="m478 304 8 9 15-19" stroke="#fff" strokeWidth="5" fill="none" strokeLinecap="round" strokeLinejoin="round" />
            <path d="M125 357c-18 32-30 69-29 118h179c-14-35-39-72-79-89-30-13-54-32-71-29Z" fill="#455e67" />
            <circle cx="165" cy="334" r="23" fill="#f4ba8e" />
            <path d="M151 313c13-17 39-9 42 12-20 1-33-3-42-12Z" fill="#111821" />
            <path d="M163 355c20 12 45 15 72 20l-42 35c-27-15-42-32-49-51Z" fill="#5947d7" />
            <path d="M196 416c36 16 74 32 115 42l-13 17c-51-3-93-13-126-34Z" fill="#25323a" />
            <rect x="215" y="385" width="67" height="44" rx="4" fill="#dce2e5" />
            <path d="M249 474h85l-16 18-88-4Z" fill="#e5e9ec" />
            <path d="M119 452c17 19 40 28 66 31" stroke="#2f3f47" strokeWidth="10" strokeLinecap="round" />
            <path d="M434 302c20 8 35 24 38 45 7 45 1 90-18 128h-55c17-43 19-83 11-120-5-22 2-42 24-53Z" fill="#222e33" />
            <circle cx="419" cy="286" r="20" fill="#bd765e" />
            <path d="M402 283c1-27 35-35 52-13-18-3-32 1-52 13Z" fill="#101820" />
            <path d="M399 309c-25 22-28 59-8 88h57c1-37-8-68-49-88Z" fill="#5947d7" />
            <path d="M403 343c7 12 20 19 37 20" stroke="#fff" strokeWidth="5" opacity=".6" />
            <path d="M88 465c16-50 8-92-21-126 49 35 73 77 72 126M117 463c-2-65 17-114 62-148-18 57-18 106 2 148" stroke="#b8c3c5" strokeWidth="7" fill="none" />
            <path d="M524 475c6-48 30-82 72-103-21 43-23 78-11 103M482 474c5-35 26-57 62-67-18 28-23 51-15 68" stroke="#344a53" strokeWidth="7" fill="none" />
            <rect x="525" y="450" width="42" height="25" fill="#5947d7" />
        </svg>
    );
}

function PasswordField({ id, label, value, onChange, error, autoComplete }) {
    const [isVisible, setVisible] = useState(false);

    return (
        <label className="booking-auth-field" htmlFor={id}>
            <span>{label}</span>
            <div className="booking-auth-password">
                <input
                    id={id}
                    type={isVisible ? 'text' : 'password'}
                    value={value}
                    onChange={onChange}
                    autoComplete={autoComplete}
                    required
                />
                <button type="button" onClick={() => setVisible((current) => !current)} aria-label="Toggle password visibility">
                    <Icon name="eye" size={20} />
                </button>
            </div>
            {error && <small>{error}</small>}
        </label>
    );
}

export function AuthPage({
    mode,
    authUser,
    authLoading,
    authError,
    authFieldError,
    loginForm,
    setLoginForm,
    registerForm,
    setRegisterForm,
    onLogin,
    onRegister,
}) {
    const isLogin = mode === 'login';

    if (authUser) {
        return <Navigate to="/" replace />;
    }

    return (
        <section className="booking-auth-page">
            <div className="booking-auth-card">
                <div className="booking-auth-visual">
                    <AuthIllustration />
                </div>

                <div className="booking-auth-panel">
                    <div className="booking-auth-inner">
                        <Link className="booking-auth-logo" to="/" aria-label="Booking home">
                            <span className="booking-brand-mark"><Icon name="plane" size={25} /></span>
                        </Link>

                        <h1>{isLogin ? 'Welcome back' : 'Create new account'}</h1>
                        <p>
                            {isLogin ? 'New here? ' : 'Already a member? '}
                            <Link to={isLogin ? '/signup' : '/signin'}>{isLogin ? 'Create an account' : 'Log in'}</Link>
                        </p>

                        {authError && <div className="booking-auth-error">{authError}</div>}

                        {isLogin ? (
                            <form className="booking-auth-form" onSubmit={onLogin}>
                                <label className="booking-auth-field" htmlFor="signinEmail">
                                    <span>Enter email id</span>
                                    <input
                                        id="signinEmail"
                                        type="email"
                                        value={loginForm.email}
                                        onChange={(event) => setLoginForm({ ...loginForm, email: event.target.value })}
                                        placeholder="user@email.com"
                                        autoComplete="email"
                                        required
                                    />
                                    {authFieldError('email') && <small>{authFieldError('email')}</small>}
                                </label>

                                <PasswordField
                                    id="signinPassword"
                                    label="Enter password"
                                    value={loginForm.password}
                                    onChange={(event) => setLoginForm({ ...loginForm, password: event.target.value })}
                                    error={authFieldError('password')}
                                    autoComplete="current-password"
                                />

                                <div className="booking-auth-row">
                                    <label><input type="checkbox" /> Remember me?</label>
                                    <button type="button">Forgot password?</button>
                                </div>

                                <button className="booking-auth-submit" type="submit" disabled={authLoading}>
                                    {authLoading ? 'Logging in...' : 'Login'}
                                </button>
                            </form>
                        ) : (
                            <form className="booking-auth-form" onSubmit={onRegister}>
                                <label className="booking-auth-field" htmlFor="signupName">
                                    <span>Enter full name</span>
                                    <input
                                        id="signupName"
                                        value={registerForm.name}
                                        onChange={(event) => setRegisterForm({ ...registerForm, name: event.target.value })}
                                        autoComplete="name"
                                        required
                                    />
                                    {authFieldError('name') && <small>{authFieldError('name')}</small>}
                                </label>

                                <label className="booking-auth-field" htmlFor="signupEmail">
                                    <span>Enter email id</span>
                                    <input
                                        id="signupEmail"
                                        type="email"
                                        value={registerForm.email}
                                        onChange={(event) => setRegisterForm({ ...registerForm, email: event.target.value })}
                                        autoComplete="email"
                                        required
                                    />
                                    {authFieldError('email') && <small>{authFieldError('email')}</small>}
                                </label>

                                <PasswordField
                                    id="signupPassword"
                                    label="Enter password"
                                    value={registerForm.password}
                                    onChange={(event) => setRegisterForm({ ...registerForm, password: event.target.value })}
                                    error={authFieldError('password')}
                                    autoComplete="new-password"
                                />

                                <PasswordField
                                    id="signupPasswordConfirmation"
                                    label="Confirm password"
                                    value={registerForm.password_confirmation}
                                    onChange={(event) => setRegisterForm({ ...registerForm, password_confirmation: event.target.value })}
                                    autoComplete="new-password"
                                />

                                <div className="booking-auth-row">
                                    <label><input type="checkbox" /> Keep me signed in</label>
                                </div>

                                <button className="booking-auth-submit" type="submit" disabled={authLoading}>
                                    {authLoading ? 'Creating account...' : 'Sign up'}
                                </button>
                            </form>
                        )}

                        <div className="booking-social-divider"><span>Or sign in with</span></div>
                        <div className="booking-social-buttons">
                            <button type="button"><b>G</b> Continue with Google</button>
                            <button type="button"><b>f</b> Continue with Facebook</button>
                        </div>
                        <footer>Copyright 2026 JasaKu. All rights reserved.</footer>
                    </div>
                </div>
            </div>
        </section>
    );
}
