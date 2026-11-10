@extends('layouts.app')

@section('title', 'Table ' . $table->table_number . ' — Digital Menu & Self-Ordering')

@section('content')
<div class="mx-auto max-w-2xl pb-32"
     x-data="guestMenuApp({
         tableId: {{ $table->id }},
         tableNumber: @js($table->table_number),
         vatRate: {{ $vatRate }},
         serviceChargeRate: {{ $serviceChargeRate }},
         initialCategory: @js($categories->first()?->slug ?? 'all'),
         oldName: @js(old('customer_name', '')),
         oldPhone: @js(old('customer_phone', '')),
     })">

    <!-- Mobile Sticky Top Bar -->
    <div class="sticky top-0 z-30 border-b border-slate-800 bg-slate-950/95 px-4 py-3.5 backdrop-blur">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="rounded-lg bg-amber-500 px-2.5 py-0.5 text-xs font-extrabold text-slate-950">
                        TABLE {{ $table->table_number }}
                    </span>
                    <span class="text-xs font-medium text-emerald-400">&bull; Verified Signed QR</span>
                </div>
                <h1 class="mt-1 text-lg font-extrabold text-white">{{ config('hotel.name') }}</h1>
            </div>

            <button type="button"
                    @click="onboardingModal = true"
                    class="rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-right text-xs transition hover:border-amber-500/50">
                <span class="block text-[10px] uppercase tracking-wider text-slate-400">Guest Details</span>
                <span class="font-bold text-amber-300" x-text="customerName ? customerName : 'Tap to Register'"></span>
            </button>
        </div>

        <!-- Department Category Pills (Starters, Mains, Desserts, Beverages) -->
        <div class="mt-3 flex gap-2 overflow-x-auto pb-1 no-scrollbar">
            <button type="button"
                    @click="activeCategory = 'all'"
                    :class="activeCategory === 'all' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-900 text-slate-300 border border-slate-800'"
                    class="shrink-0 rounded-full px-4 py-1.5 text-xs transition">
                All Departments
            </button>
            @foreach ($categories as $category)
                <button type="button"
                        @click="activeCategory = '{{ $category->slug }}'"
                        :class="activeCategory === '{{ $category->slug }}' ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-900 text-slate-300 border border-slate-800'"
                        class="shrink-0 rounded-full px-4 py-1.5 text-xs transition">
                    {{ $category->name }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Menu Items Grouped by Department -->
    <div class="space-y-8 px-4 pt-5">
        @foreach ($categories as $category)
            <section x-show="activeCategory === 'all' || activeCategory === '{{ $category->slug }}'" x-transition>
                <div class="mb-3 flex items-center justify-between border-b border-slate-800 pb-2">
                    <div>
                        <h2 class="text-base font-extrabold tracking-tight text-white">{{ $category->name }}</h2>
                        <span class="text-[11px] uppercase tracking-wider text-slate-400">
                            Station: {{ ucfirst($category->station) }}
                        </span>
                    </div>
                    <span class="rounded-full bg-slate-900 px-2.5 py-0.5 text-xs text-slate-400">
                        {{ $category->menuItems->count() }} items
                    </span>
                </div>

                <div class="space-y-3">
                    @foreach ($category->menuItems as $item)
                        @php
                            $canOrder = $item->is_available && ($item->current_stock === -1 || $item->current_stock > 0);
                        @endphp
                        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-4 transition {{ $canOrder ? 'hover:border-slate-700' : 'opacity-60' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="grow">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-bold text-white">{{ $item->name }}</h3>
                                        @if ($item->is_vat_exempt)
                                            <span class="rounded bg-emerald-500/15 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-emerald-300">
                                                VAT Exempt
                                            </span>
                                        @endif
                                        @if (! $canOrder)
                                            <span class="rounded bg-rose-500/20 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-300">
                                                Out of Stock
                                            </span>
                                        @elseif ($item->current_stock > 0 && $item->current_stock <= 10)
                                            <span class="rounded bg-amber-500/15 px-1.5 py-0.5 text-[10px] font-semibold text-amber-300">
                                                Only {{ $item->current_stock }} left
                                            </span>
                                        @endif
                                    </div>
                                    @if ($item->description)
                                        <p class="mt-1 text-xs leading-relaxed text-slate-400">{{ $item->description }}</p>
                                    @endif
                                    <div class="mt-2.5 text-sm font-extrabold text-amber-400">
                                        LKR {{ number_format((float) $item->price, 2) }}
                                    </div>
                                </div>

                                @if ($canOrder)
                                    <div class="flex shrink-0 flex-col items-end gap-2">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    x-show="getQty({{ $item->id }}) > 0"
                                                    @click="decrementItem({{ $item->id }})"
                                                    class="flex h-8 w-8 items-center justify-center rounded-xl border border-slate-700 bg-slate-950 text-base font-bold text-white hover:border-amber-500">
                                                &minus;
                                            </button>

                                            <span x-show="getQty({{ $item->id }}) > 0"
                                                  x-text="getQty({{ $item->id }})"
                                                  class="min-w-[1.5rem] text-center text-sm font-extrabold text-white"></span>

                                            <button type="button"
                                                    @click="incrementItem({
                                                        id: {{ $item->id }},
                                                        name: @js($item->name),
                                                        price: {{ (float) $item->price }},
                                                        isVatExempt: {{ $item->is_vat_exempt ? 'true' : 'false' }},
                                                        maxStock: {{ $item->current_stock }}
                                                    })"
                                                    class="flex h-8 items-center justify-center rounded-xl bg-amber-500 px-3 text-xs font-extrabold text-slate-950 shadow hover:bg-amber-400">
                                                <span x-show="getQty({{ $item->id }}) === 0">+ Add</span>
                                                <span x-show="getQty({{ $item->id }}) > 0">+</span>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Special preparation notes input when item is in cart -->
                            <div x-show="getQty({{ $item->id }}) > 0" x-cloak class="mt-3 border-t border-slate-800/80 pt-2.5">
                                <input type="text"
                                       :value="getNotes({{ $item->id }})"
                                       @input="setNotes({{ $item->id }}, $event.target.value)"
                                       maxlength="255"
                                       placeholder="Special kitchen/bar note (e.g., less spicy, no ice, allergy info)..."
                                       class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:border-amber-500 focus:outline-none">
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <!-- Bottom Sticky Cart Bar -->
    <div x-show="totalItemsCount > 0"
         x-cloak
         class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-800 bg-slate-900/95 p-4 shadow-2xl backdrop-blur">
        <div class="mx-auto flex max-w-2xl items-center justify-between gap-4">
            <div>
                <div class="text-xs text-slate-400">
                    <span class="font-bold text-white" x-text="totalItemsCount"></span> item(s) &bull; Est. Total
                </div>
                <div class="text-lg font-extrabold text-amber-400">
                    LKR <span x-text="estimatedGrandTotal.toFixed(2)"></span>
                </div>
            </div>

            <button type="button"
                    @click="cartDrawerOpen = true"
                    class="rounded-2xl bg-amber-500 px-6 py-3 text-sm font-extrabold text-slate-950 shadow-lg transition hover:bg-amber-400">
                Review &amp; Place Order &rarr;
            </button>
        </div>
    </div>

    <!-- Guest Onboarding Modal -->
    <div x-show="onboardingModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
            <div class="flex items-center justify-between">
                <span class="rounded-full bg-amber-500/15 px-3 py-1 text-xs font-bold uppercase text-amber-300">
                    Table {{ $table->table_number }} Welcome
                </span>
                <button type="button"
                        x-show="customerName && isValidPhone(customerPhone)"
                        @click="onboardingModal = false"
                        class="text-xs text-slate-400 hover:text-white">
                    Close &times;
                </button>
            </div>

            <h2 class="mt-3 text-xl font-extrabold text-white">Welcome to {{ config('hotel.name') }}</h2>
            <p class="mt-1 text-xs text-slate-400">
                Enter your name and mobile number to start ordering and receive your digital SMS receipt upon checkout.
            </p>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Your Name</label>
                    <input type="text"
                           x-model.trim="customerName"
                           maxlength="100"
                           placeholder="e.g., Kasun Perera"
                           class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Mobile Number (Sri Lanka)</label>
                    <input type="tel"
                           x-model.trim="customerPhone"
                           maxlength="20"
                           placeholder="0771234567 or +94771234567"
                           class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-none">
                    <p x-show="customerPhone.length > 0 && !isValidPhone(customerPhone)"
                       class="mt-1 text-xs text-rose-400">
                        Format required: 07XXXXXXXX, 947XXXXXXXX, or +947XXXXXXXX
                    </p>
                </div>

                <button type="button"
                        @click="completeOnboarding()"
                        class="w-full rounded-xl bg-amber-500 py-3 text-sm font-extrabold text-slate-950 transition hover:bg-amber-400">
                    Continue to Menu
                </button>
            </div>
        </div>
    </div>

    <!-- Slide-Over Cart & Order Submission Drawer -->
    <div x-show="cartDrawerOpen"
         x-cloak
         class="fixed inset-0 z-50 flex flex-col justify-end bg-slate-950/80 backdrop-blur-sm">
        <div @click.away="cartDrawerOpen = false"
             class="mx-auto max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-t-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div>
                    <h2 class="text-lg font-extrabold text-white">Your Table {{ $table->table_number }} Order</h2>
                    <p class="text-xs text-slate-400">Initial Batch #1 &bull; Stock verified with pessimistic locking</p>
                </div>
                <button type="button" @click="cartDrawerOpen = false" class="rounded-lg bg-slate-800 px-3 py-1 text-xs text-slate-300">
                    Back to Menu
                </button>
            </div>

            <form method="POST" action="{{ route('order.place') }}" class="mt-4 space-y-5"
                  data-confirm-type="success"
                  data-confirm-badge="Table {{ $table->table_number }} Order Confirmation"
                  data-confirm-title="Place Your Order Now?"
                  data-confirm-message="Your order will be sent directly to our service team and kitchen for preparation."
                  data-confirm-button="✓ Yes, Place My Order">
                @csrf
                <input type="hidden" name="restaurant_table_id" value="{{ $table->id }}">

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Customer Name *</label>
                        <input type="text" name="customer_name" x-model="customerName" required maxlength="100"
                               class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-white focus:border-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400">Sri Lankan Mobile *</label>
                        <input type="tel" name="customer_phone" x-model="customerPhone" required maxlength="20"
                               pattern="^(?:0|94|\+94)?7[0-9]{8}$"
                               class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2 text-sm text-white focus:border-amber-500 focus:outline-none">
                    </div>
                </div>

                <!-- Dynamic Cart Hidden Inputs & Item List -->
                <div class="space-y-3">
                    <template x-for="(entry, index) in cartArray" :key="entry.id">
                        <div class="rounded-2xl border border-slate-800 bg-slate-950 p-3.5">
                            <input type="hidden" :name="`items[${index}][menu_item_id]`" :value="entry.id">
                            <input type="hidden" :name="`items[${index}][quantity]`" :value="entry.quantity">
                            <input type="hidden" :name="`items[${index}][notes]`" :value="entry.notes">

                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-bold text-white" x-text="entry.name"></div>
                                    <div class="text-xs text-slate-400">
                                        LKR <span x-text="entry.price.toFixed(2)"></span> &times; <span x-text="entry.quantity"></span>
                                    </div>
                                    <div x-show="entry.notes" class="mt-1 text-xs italic text-amber-300" x-text="'Note: ' + entry.notes"></div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-extrabold text-amber-400">
                                        LKR <span x-text="(entry.price * entry.quantity).toFixed(2)"></span>
                                    </span>
                                    <div class="flex items-center gap-1">
                                        <button type="button" @click="decrementItem(entry.id)" class="h-7 w-7 rounded-lg bg-slate-800 text-xs font-bold text-white">&minus;</button>
                                        <button type="button" @click="incrementItem(entry)" class="h-7 w-7 rounded-lg bg-amber-500 text-xs font-bold text-slate-950">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Financial Estimate Summary -->
                <div class="rounded-2xl border border-slate-800 bg-slate-950/90 p-4 text-xs space-y-1.5">
                    <div class="flex justify-between text-slate-300">
                        <span>Gross Subtotal</span>
                        <span>LKR <span x-text="grossSubtotal.toFixed(2)"></span></span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Service Charge ({{ $serviceChargeRate }}%)</span>
                        <span>LKR <span x-text="estimatedServiceCharge.toFixed(2)"></span></span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Estimated VAT ({{ $vatRate }}%)</span>
                        <span>LKR <span x-text="estimatedVat.toFixed(2)"></span></span>
                    </div>
                    <div class="flex justify-between border-t border-slate-800 pt-2 text-sm font-extrabold text-amber-400">
                        <span>Estimated Grand Total</span>
                        <span>LKR <span x-text="estimatedGrandTotal.toFixed(2)"></span></span>
                    </div>
                </div>

                <button type="submit"
                        :disabled="cartArray.length === 0 || !customerName || !isValidPhone(customerPhone)"
                        class="w-full rounded-2xl bg-amber-500 py-3.5 text-sm font-extrabold text-slate-950 shadow-lg transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50">
                    Confirm &amp; Send Order to Table Queue
                </button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function guestMenuApp(config) {
    return {
        activeCategory: 'all',
        onboardingModal: !config.oldName,
        cartDrawerOpen: false,
        customerName: config.oldName || '',
        customerPhone: config.oldPhone || '',
        cart: {},

        isValidPhone(phone) {
            return /^(?:0|94|\+94)?7[0-9]{8}$/.test((phone || '').replace(/\s+/g, ''));
        },

        completeOnboarding() {
            if (!this.customerName || !this.isValidPhone(this.customerPhone)) {
                window.hotelNotify({
                    type: 'warning',
                    title: 'Guest Details Required',
                    message: 'Please enter your name and a valid Sri Lankan mobile number (e.g., 0771234567) to continue.'
                });
                return;
            }
            this.onboardingModal = false;
            window.hotelNotify({
                type: 'success',
                title: `Welcome, ${this.customerName}!`,
                message: 'You can now browse the menu and add items to your table order.'
            });
        },

        get cartArray() {
            return Object.values(this.cart);
        },

        get totalItemsCount() {
            return this.cartArray.reduce((sum, item) => sum + item.quantity, 0);
        },

        get grossSubtotal() {
            return this.cartArray.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        },

        get estimatedServiceCharge() {
            return Math.round(this.grossSubtotal * (config.serviceChargeRate / 100) * 100) / 100;
        },

        get estimatedVat() {
            const taxableSubtotal = this.cartArray.reduce((sum, item) => {
                return sum + (item.isVatExempt ? 0 : item.price * item.quantity);
            }, 0);
            return Math.round(taxableSubtotal * (config.vatRate / 100) * 100) / 100;
        },

        get estimatedGrandTotal() {
            return Math.round((this.grossSubtotal + this.estimatedServiceCharge + this.estimatedVat) * 100) / 100;
        },

        getQty(id) {
            return this.cart[id] ? this.cart[id].quantity : 0;
        },

        getNotes(id) {
            return this.cart[id] ? this.cart[id].notes : '';
        },

        setNotes(id, value) {
            if (this.cart[id]) {
                this.cart[id].notes = value;
            }
        },

        incrementItem(item) {
            const current = this.cart[item.id] ? this.cart[item.id].quantity : 0;
            if (item.maxStock !== -1 && current >= item.maxStock) {
                window.hotelNotify({
                    type: 'warning',
                    title: 'Stock Limit Reached',
                    message: `Only ${item.maxStock} portion(s) of "${item.name}" are currently available.`
                });
                return;
            }
            if (!this.cart[item.id]) {
                this.cart[item.id] = {
                    id: item.id,
                    name: item.name,
                    price: Number(item.price),
                    isVatExempt: Boolean(item.isVatExempt),
                    maxStock: item.maxStock,
                    quantity: 1,
                    notes: ''
                };
            } else {
                this.cart[item.id].quantity++;
            }
        },

        decrementItem(id) {
            if (!this.cart[id]) return;
            if (this.cart[id].quantity <= 1) {
                delete this.cart[id];
            } else {
                this.cart[id].quantity--;
            }
        }
    };
}
</script>
@endpush
@endsection

