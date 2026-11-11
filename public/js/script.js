const $ = (selector) => document.querySelector(selector);
const $$ = (selector) => document.querySelectorAll(selector);


/* =========================
   MOBILE MENU
========================= */

const hamburger = $('#hamb');
const nav = $('#nav');

hamburger?.addEventListener('click', () => {
    nav?.classList.toggle('open');
});

$$('#nav a').forEach(link => {
    link.addEventListener('click', () => {
        nav?.classList.remove('open');
    });
});


/* =========================
   TOAST
========================= */

function toast(message) {
    const box = $('#toast');

    if (!box) return;

    box.textContent = message;
    box.classList.add('show');

    clearTimeout(window.toastTimer);

    window.toastTimer = setTimeout(() => {
        box.classList.remove('show');
    }, 2200);
}


/* =========================
   CART
========================= */

let cart = [];

const MAX_QTY = 5;


/* Demo products
   Temporary only.
   Later these will come from
   Symfony + database.
*/

const products = {
    1: {
        id: 1,
        name: 'Classic Latte',
        price: 120,
        stock: 10
    },

    2: {
        id: 2,
        name: 'Iced Latte',
        price: 130,
        stock: 10
    },

    3: {
        id: 3,
        name: 'Fresh Croissant',
        price: 95,
        stock: 10
    },

    4: {
        id: 4,
        name: 'Fundador Special',
        price: 145,
        stock: 10
    }
};


/* =========================
   ADD TO CART
========================= */

$$('.add').forEach(button => {

    button.addEventListener('click', () => {

        const id = Number(button.dataset.id);

        const product = products[id];

        if (!product) {
            toast('Product not found.');
            return;
        }

        const existing = cart.find(item => item.id === id);

        if (existing) {

            if (existing.qty >= MAX_QTY) {
                toast('Maximum quantity reached.');
                return;
            }

            if (existing.qty >= product.stock) {
                toast('Not enough stock.');
                return;
            }

            existing.qty++;

        } else {

            cart.push({
                ...product,
                qty: 1
            });

        }

        renderCart();

        toast('Added to your order.');
    });

});


/* =========================
   RENDER CART
========================= */

function renderCart() {

    const box = $('#items');

    if (!box) return;

    let total = 0;
    let count = 0;

    if (cart.length === 0) {

        box.innerHTML = `
            <p>Your order is empty.</p>
        `;

    } else {

        box.innerHTML = cart.map(item => {

            const subtotal = item.price * item.qty;

            total += subtotal;
            count += item.qty;

            return `
                <div class="cart-row">

                    <div>
                        <b>${item.name}</b>

                        <small>
                            ₱${item.price.toFixed(2)} each
                        </small>
                    </div>

                    <div class="cart-controls">

                        <b>
                            ₱${subtotal.toFixed(2)}
                        </b>

                        <div class="qty-controls">

                            <button
                                class="qty"
                                data-id="${item.id}"
                                data-change="-1"
                            >
                                −
                            </button>

                            <span>
                                ${item.qty}
                            </span>

                            <button
                                class="qty"
                                data-id="${item.id}"
                                data-change="1"
                            >
                                +
                            </button>

                        </div>

                        <button
                            class="remove"
                            data-id="${item.id}"
                        >
                            Remove
                        </button>

                    </div>

                </div>
            `;

        }).join('');
    }


    if ($('#total')) {
        $('#total').textContent =
            '₱' +
            total.toLocaleString(undefined, {
                minimumFractionDigits: 2
            });
    }


    if ($('#count')) {
        $('#count').textContent = count;
    }


    /* Quantity buttons */

    $$('.qty').forEach(button => {

        button.addEventListener('click', () => {

            const id = Number(button.dataset.id);
            const change = Number(button.dataset.change);

            changeQuantity(id, change);

        });

    });


    /* Remove buttons */

    $$('.remove').forEach(button => {

        button.addEventListener('click', () => {

            const id = Number(button.dataset.id);

            cart = cart.filter(item => item.id !== id);

            renderCart();

        });

    });

}


/* =========================
   CHANGE QUANTITY
========================= */

function changeQuantity(id, change) {

    const item = cart.find(item => item.id === id);

    if (!item) return;


    const newQuantity = item.qty + change;


    if (newQuantity <= 0) {

        cart = cart.filter(item => item.id !== id);

    } else if (newQuantity > MAX_QTY) {

        toast('Maximum quantity is 5.');

    } else if (newQuantity > item.stock) {

        toast('Not enough stock.');

    } else {

        item.qty = newQuantity;

    }


    renderCart();
}


/* =========================
   OPEN CART
========================= */

$('#cartOpen')?.addEventListener('click', () => {

    $('#cart')?.classList.add('open');

});


/* =========================
   CLOSE CART
========================= */

$('#close')?.addEventListener('click', () => {

    $('#cart')?.classList.remove('open');

});


/* =========================
   GALLERY FILTER
========================= */

$$('.tabs button').forEach(button => {

    button.addEventListener('click', () => {

        $$('.tabs button').forEach(item => {
            item.classList.remove('selected');
        });

        button.classList.add('selected');


        const filter = button.dataset.filter;


        $$('.gallery-grid img').forEach(image => {

            const type = image.dataset.type;


            if (filter === 'all') {

                image.classList.remove('hide');

            } else if (type === filter) {

                image.classList.remove('hide');

            } else {

                image.classList.add('hide');

            }

        });

    });

});


/* =========================
   PAYMENT METHOD
========================= */

$$('input[name="payment_method"]').forEach(radio => {

    radio.addEventListener('change', () => {

        const box = $('#gcashReferenceBox');
        const input = $('#gcashReference');

        const isGCash =
            radio.value === 'GCash' &&
            radio.checked;


        if (box) {
            box.style.display =
                isGCash ? 'block' : 'none';
        }


        if (input) {
            input.required = isGCash;
        }

    });

});


/* =========================
   CHECKOUT
========================= */

$('#checkout')?.addEventListener('click', () => {

    if (cart.length === 0) {

        toast('Please add an item first.');

        return;
    }


    const payment =
        document.querySelector(
            'input[name="payment_method"]:checked'
        )?.value || '';


    if (!payment) {

        toast('Please select Cash or GCash.');

        return;
    }


    const reference =
        $('#gcashReference')?.value.trim() || '';


    if (payment === 'GCash' && !reference) {

        toast(
            'Please enter the GCash reference number.'
        );

        return;
    }


    /* Temporary frontend checkout */

    toast('Order placed successfully!');


    cart = [];

    renderCart();


    setTimeout(() => {

        $('#cart')?.classList.remove('open');

    }, 1200);

});


/* =========================
   START
========================= */

renderCart()