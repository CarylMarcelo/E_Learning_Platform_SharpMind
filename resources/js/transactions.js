const member = document.getElementById('memberSelect');
const level = document.getElementById('levelSelect');
const currency = value => value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function updateFee() {
    if (!member || !level) { return; }
    const selectedMember = member.selectedOptions[0];
    const fee = Number(level.selectedOptions[0]?.dataset.fee || 0);
    const discount = Number(selectedMember?.dataset.discount || 0);
    const deduction = fee * discount / 100;
    document.getElementById('baseFeeDisplay').textContent = currency(fee);
    document.getElementById('discountRow').hidden = discount <= 0;
    document.getElementById('categoryNameDisplay').textContent = selectedMember?.dataset.category || '';
    document.getElementById('discountPercentDisplay').textContent = discount;
    document.getElementById('discountAmountDisplay').textContent = currency(deduction);
    document.getElementById('finalFeeDisplay').textContent = currency(fee - deduction);
}
member?.addEventListener('change', () => {
    const memberLevel = member.selectedOptions[0]?.dataset.level;
    if (memberLevel) { level.value = memberLevel; }
    updateFee();
});
level?.addEventListener('change', updateFee);
if (member?.value && !level?.dataset.restoredLevel) {
    level.value = member.selectedOptions[0]?.dataset.level || '';
}
updateFee();
