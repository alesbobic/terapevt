const toggle = document.querySelector('[data-menu-toggle]');
const nav = document.querySelector('[data-nav]');
const year = document.querySelector('[data-year]');
if (year) year.textContent = new Date().getFullYear();
if (toggle && nav) {
  toggle.addEventListener('click', () => {
    const open = nav.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', String(open));
  });
  nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
    nav.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
  }));
}
const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) entry.target.classList.add('is-visible');
  });
}, { threshold: 0.12 });
document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));

const form = document.querySelector('#contact-form');
if (form) {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const messages = form.querySelector('.messages');
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    const button = form.querySelector('button[type="submit"]');
    const original = button.textContent;
    button.disabled = true;
    button.textContent = 'Pošiljam ...';
    try {
      const response = await fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form)
      });
      const data = await response.json();
      const type = data.type === 'success' ? 'success' : 'danger';
      messages.innerHTML = `<div class="alert alert-${type}">${data.message}</div>`;
      if (type === 'success') form.reset();
    } catch (error) {
      messages.innerHTML = '<div class="alert alert-danger">Sporočila trenutno ni bilo mogoče poslati. Poskusite pozneje ali pokličite.</div>';
    } finally {
      button.disabled = false;
      button.textContent = original;
    }
  });
}

// Cookie consent + optional Google scripts
(function () {
  const CONSENT_KEY = 'terapevt_cookie_consent';
  const banner = document.querySelector('[data-cookie-banner]');
  const accept = document.querySelector('[data-cookie-accept]');
  const reject = document.querySelector('[data-cookie-reject]');

  // Vpišite svoje Google ID-je, ko jih imate pripravljene.
  const GOOGLE_ADS_ID = ''; // primer: AW-XXXXXXXXXX
  const GA_MEASUREMENT_ID = ''; // primer: G-XXXXXXXXXX

  function setCookie(name, value, days) {
    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value}; Max-Age=${maxAge}; Path=/; SameSite=Lax`;
  }

  function getConsent() {
    return localStorage.getItem(CONSENT_KEY);
  }

  function saveConsent(value) {
    localStorage.setItem(CONSENT_KEY, value);
    setCookie('terapevt_cookie_consent', value, 180);
  }

  function loadScript(src) {
    if (!src || document.querySelector(`script[src="${src}"]`)) return;
    const script = document.createElement('script');
    script.async = true;
    script.src = src;
    document.head.appendChild(script);
  }

  function enableGoogleCookies() {
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };
    window.gtag('consent', 'update', {
      ad_storage: 'granted',
      analytics_storage: 'granted',
      ad_user_data: 'granted',
      ad_personalization: 'granted'
    });

    if (GA_MEASUREMENT_ID) {
      loadScript(`https://www.googletagmanager.com/gtag/js?id=${GA_MEASUREMENT_ID}`);
      window.gtag('js', new Date());
      window.gtag('config', GA_MEASUREMENT_ID, { anonymize_ip: true });
    }
    if (GOOGLE_ADS_ID) {
      loadScript(`https://www.googletagmanager.com/gtag/js?id=${GOOGLE_ADS_ID}`);
      window.gtag('config', GOOGLE_ADS_ID);
    }
  }

  window.dataLayer = window.dataLayer || [];
  window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };
  window.gtag('consent', 'default', {
    ad_storage: 'denied',
    analytics_storage: 'denied',
    ad_user_data: 'denied',
    ad_personalization: 'denied'
  });

  const consent = getConsent();
  if (consent === 'accepted') {
    enableGoogleCookies();
  } else if (!consent && banner) {
    banner.hidden = false;
  }

  if (accept) {
    accept.addEventListener('click', () => {
      saveConsent('accepted');
      if (banner) banner.hidden = true;
      enableGoogleCookies();
    });
  }

  if (reject) {
    reject.addEventListener('click', () => {
      saveConsent('rejected');
      if (banner) banner.hidden = true;
    });
  }
})();
