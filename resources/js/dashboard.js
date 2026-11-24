const donut = document.querySelector('.membership-donut');
if (donut) {
    const total = Number(donut.dataset.total);
    const active = total ? Number(donut.dataset.active) / total * 360 : 0;
    const pending = total ? Number(donut.dataset.pending) / total * 360 : 0;
    donut.style.setProperty('--active-angle', active + 'deg');
    donut.style.setProperty('--pending-angle', active + pending + 'deg');
}
