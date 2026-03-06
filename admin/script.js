/* ====== admin/script.js – Christ Merveille Energie ====== */

/* ── Auth guard ── */
async function checkAuth() {
  try {
    const r = await fetch('../backend/auth.php?action=check');
    const d = await r.json();
    if (!d.success) { window.location.href = 'login.html'; return null; }
    return d.username;
  } catch {
    window.location.href = 'login.html';
    return null;
  }
}

/* ── Toast ── */
function showToast(msg, type = 'success') {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle' };
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<i class="fas ${icons[type] || icons.success}"></i> ${msg}`;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}

/* ── Fetch helpers ── */
async function apiGet(url) {
  const r = await fetch(url);
  if (!r.ok) throw new Error('Erreur réseau');
  return r.json();
}

async function apiPost(url, data) {
  const r = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  return r.json();
}

/* ── Logout ── */
async function logout() {
  await fetch('../backend/auth.php?action=logout');
  window.location.href = 'login.html';
}

/* ── Sidebar mobile toggle ── */
function initSidebar() {
  const toggle = document.getElementById('sidebarToggle');
  const close = document.getElementById('sidebarClose');
  const overlay = document.getElementById('sidebarOverlay');
  const sidebar = document.querySelector('.sidebar');

  const openSidebar = () => {
    sidebar.classList.add('open');
    overlay?.classList.add('active');
  };

  const closeSidebar = () => {
    sidebar.classList.remove('open');
    overlay?.classList.remove('active');
  };

  if (toggle) toggle.addEventListener('click', openSidebar);
  if (close) close.addEventListener('click', closeSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);
}

/* ── Format date ── */
function formatDate(str) {
  if (!str) return '—';
  const d = new Date(str);
  return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

/* ── Escape HTML ── */
function esc(s) {
  return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

/* ── Set username in topbar ── */
function setUser(username) {
  const el = document.getElementById('topbarUser');
  if (el) el.textContent = username || 'Admin';
  const av = document.getElementById('topbarAvatar');
  if (av) av.textContent = (username || 'A')[0].toUpperCase();
}

/* ── Init (called by each page) ── */
async function adminInit() {
  initSidebar();

  // Logout button
  document.getElementById('btnLogout')?.addEventListener('click', () => {
    if (confirm('Vous déconnecter ?')) logout();
  });

  const username = await checkAuth();
  if (username) setUser(username);
  return username;
}
