import { initializeMemberMap } from './member-map';

const phoneCountry = document.getElementById('phone_country');
const phoneNumber = document.getElementById('phone');
function updatePhoneCountry() {
    const selectedCountry = phoneCountry?.selectedOptions[0];
    if (!selectedCountry || !phoneNumber) { return; }
    phoneNumber.placeholder = phoneCountry.value === 'PH' ? '912 345 6789' : 'Phone number';
    document.getElementById('phone-country-code').textContent = `(+${selectedCountry.dataset.dialCode})`;
    document.getElementById('phone-country-flag').setAttribute('href', `${phoneCountry.dataset.flagsUrl}#${selectedCountry.value.toLowerCase()}`);
    phoneCountry.title = selectedCountry.textContent.trim();
}
phoneCountry?.addEventListener('change', updatePhoneCountry);
updatePhoneCountry();


const category = document.getElementById('categorySelect');
const level = document.getElementById('levelSelect');
const currency = value => value.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function updateSummary() {
    const selectedLevel = level?.selectedOptions[0];
    const summary = document.getElementById('levelSummary');
    if (!selectedLevel || !summary) { return; }
    summary.hidden = !selectedLevel.value;
    if (summary.hidden) { return; }
    const fee = Number(selectedLevel.dataset.fee || 0);
    const selectedCategory = category?.selectedOptions[0];
    const discount = Number(selectedCategory?.dataset.discount || 0);
    const feeInCentavos = Math.round(fee * 100);
    const discountBasisPoints = Math.round(discount * 100);
    const totalInCentavos = Math.round(feeInCentavos * (10000 - discountBasisPoints) / 10000);
    document.getElementById('summaryBaseFee').textContent = currency(feeInCentavos / 100);
    document.getElementById('summaryDiscountRow').hidden = discount <= 0;
    document.getElementById('summaryCategoryName').textContent = selectedCategory?.dataset.name || 'Category';
    document.getElementById('summaryDiscountPercent').textContent = discount;
    document.getElementById('summaryDiscountAmount').textContent = currency((feeInCentavos - totalInCentavos) / 100);
    document.getElementById('summaryFee').textContent = currency(totalInCentavos / 100);
    document.getElementById('summaryDays').textContent = selectedLevel.dataset.days || '0';
    const benefits = JSON.parse(selectedLevel.dataset.benefits || '[]');
    const template = document.getElementById('summary-benefit-template');
    document.getElementById('summaryBenefits').replaceChildren(...benefits.map(benefit => {
        const item = template.content.firstElementChild.cloneNode(true);
        item.querySelector('span').textContent = benefit;
        return item;
    }));
}
category?.addEventListener('change', updateSummary);
level?.addEventListener('change', updateSummary);
updateSummary();
initializeMemberMap();
