document.addEventListener('DOMContentLoaded', () => {
  const accountButton = document.querySelector('.account-toggle');
  const accountMenu = document.querySelector('.account-menu');
  if (accountButton && accountMenu) {
    accountButton.addEventListener('click', () => {
      const isOpen = accountMenu.classList.toggle('open');
      accountButton.setAttribute('aria-expanded', String(isOpen));
    });
    document.addEventListener('click', (event) => {
      if (!event.target.closest('.account-wrap')) {
        accountMenu.classList.remove('open');
        accountButton.setAttribute('aria-expanded', 'false');
      }
    });
  }
  const searchForm = document.querySelector('.search-form');
  const searchButton = document.querySelector('.search-btn');
  if (searchForm && searchButton) {
    searchButton.addEventListener('click', (event) => {
      const input = searchForm.querySelector('input');
      if (!input.value.trim() && !searchForm.classList.contains('open')) {
        event.preventDefault();
        searchForm.classList.add('open');
        input.focus();
      }
    });
  }
});
function showNotice(message) {
  let notice = document.querySelector('.notice');
  if (!notice) {
    notice = document.createElement('div');
    notice.className = 'notice';
    notice.setAttribute('role', 'status');
    document.body.appendChild(notice);
  }
  notice.textContent = message;
  notice.hidden = false;
  window.clearTimeout(window.kcvNoticeTimer);
  window.kcvNoticeTimer = window.setTimeout(() => { notice.hidden = true; }, 3000);
}
