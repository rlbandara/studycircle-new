// assets/js/main.js: tiny helpers used on every page

// Ask before deleting: <a href="..." data-confirm="Delete this note?">Delete</a>
// or <form data-confirm="Delete this note?"> ... </form>
document.addEventListener('click', function (e) {
  var el = e.target.closest('a[data-confirm], button[data-confirm]');
  if (el && !window.confirm(el.getAttribute('data-confirm'))) {
    e.preventDefault();
  }
});
document.addEventListener('submit', function (e) {
  var msg = e.target.getAttribute('data-confirm');
  if (msg && !window.confirm(msg)) {
    e.preventDefault();
  }
});

// Flash messages fade away after 6 seconds
window.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.sc-alert').forEach(function (a) {
    setTimeout(function () { a.style.display = 'none'; }, 6000);
  });
});
