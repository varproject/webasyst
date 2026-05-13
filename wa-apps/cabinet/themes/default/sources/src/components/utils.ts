export function repeat<T>(count: number, callback: (idx: number) => T): T[] {
    return new Array(count).fill(0).map((item, itemIdx) => callback(itemIdx));
}

export function url(page: string): string {
    if (page === 'dashboard') {
        return 'index.html';
    }
    if (page === 'orders-list') {
        return 'app-orders-list.html';
    }
    if (page === 'order') {
        return 'app-order.html';
    }
    if (page === 'customers-list') {
        return 'app-customers-list.html';
    }
    if (page === 'customer') {
        return 'app-customer.html';
    }
    if (page === 'products-list') {
        return 'app-products-list.html';
    }
    if (page === 'product') {
        return 'app-product.html';
    }
    if (page === 'categories-list') {
        return 'app-categories-list.html';
    }
    if (page === 'category') {
        return 'app-category.html';
    }
    if (page === 'coupons-list') {
        return 'app-coupons-list.html';
    }
    if (page === 'coupon') {
        return 'app-coupon.html';
    }
    if (page === 'inbox-list') {
        return 'app-inbox-list.html';
    }
    if (page === 'inbox-conversation') {
        return 'app-inbox-conversation.html';
    }
    if (page === 'settings-toc') {
        return 'app-settings-toc.html';
    }
    if (page === 'settings-form') {
        return 'app-settings-form.html';
    }
    if (page === 'terms') {
        return 'page-terms.html';
    }
    if (page === 'auth/sign-in') {
        return 'auth-sign-in.html';
    }
    if (page === 'auth/sign-up') {
        return 'auth-sign-up.html';
    }
    if (page === 'auth/forgot-password') {
        return 'auth-forgot-password.html';
    }

    return '#';
}
