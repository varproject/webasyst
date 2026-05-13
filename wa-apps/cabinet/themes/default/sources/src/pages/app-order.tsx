import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import { useSvg } from '@scompiler/0003-product/.scompiler/hooks';
import Image from "../components/Image";
import Price from "../components/Price";
import PageHeader from "../components/PageHeader";
import { url } from "../components/utils";

export default function() {
    const svg = useSvg();
    const imageSize = 16 * 2.5;

    const items = [
        {
            image: 'images/products/product-2.jpg',
            name: 'Brandix Brake Kit BDX-750Z370-S',
            price: 849,
            quantity: 1,
            total: 849,
        },
        {
            image: 'images/products/product-7.jpg',
            name: 'Glossy Gray 19" Aluminium Wheel AR-19',
            price: 699,
            quantity: 2,
            total: 1398,
        },
        {
            image: 'images/products/product-16.jpg',
            name: 'Twin Exhaust Pipe From Brandix Z54',
            price: 1210,
            quantity: 3,
            total: 3630,
        },
    ];

    const main = (
        <>
            <div className="sa-card-area">
                <textarea className="sa-card-area__area" rows={2} placeholder="Notes about order" />
                <div className="sa-card-area__card">
                    {svg('feather/edit')}
                </div>
            </div>

            <div className="card mt-5">
                <div className="card-body px-5 py-4 d-flex align-items-center justify-content-between">
                    <h2 className="mb-0 fs-exact-18 me-4">Items</h2>
                    <div className="text-muted fs-exact-14">
                        <a href="#">Edit items</a>
                    </div>
                </div>
                <div className="table-responsive">
                    <table className="sa-table">
                        <tbody>
                            {items.map((item, itemIdx) => (
                                <tr key={itemIdx}>
                                    <td className="min-w-20x">
                                        <div className="d-flex align-items-center">
                                            <Image src={item.image} size={imageSize} className="me-4" />
                                            <a href={url('product')} className="text-reset">
                                                {item.name}
                                            </a>
                                        </div>
                                    </td>
                                    <td className="text-end">
                                        <Price value={item.price} />
                                    </td>
                                    <td className="text-end">
                                        {item.quantity}
                                    </td>
                                    <td className="text-end">
                                        <Price value={item.total} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                        <tbody className="sa-table__group">
                            <tr>
                                <td colSpan={3}>Subtotal</td>
                                <td className="text-end"><Price value={5877} /></td>
                            </tr>
                            <tr>
                                <td colSpan={3}>Store Credit</td>
                                <td className="text-end"><Price value={-20} /></td>
                            </tr>
                            <tr>
                                <td colSpan={3}>
                                    Shipping
                                    <div className="text-muted fs-exact-13">via FedEx International</div>
                                </td>
                                <td className="text-end"><Price value={25} /></td>
                            </tr>
                        </tbody>
                        <tbody>
                            <tr>
                                <td colSpan={3}>Total</td>
                                <td className="text-end"><Price value={5882} /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="card mt-5">
                <div className="card-body px-5 py-4 d-flex align-items-center justify-content-between">
                    <h2 className="mb-0 fs-exact-18 me-4">Transactions</h2>
                    <div className="text-muted fs-exact-14">
                        <a href="#">Add transaction</a>
                    </div>
                </div>
                <div className="table-responsive">
                    <table className="sa-table text-nowrap">
                        <tbody>
                            <tr>
                                <td>
                                    Payment
                                    <div className="text-muted fs-exact-13">via PayPal</div>
                                </td>
                                <td>October 7, 2020</td>
                                <td className="text-end"><Price value={2000} /></td>
                            </tr>
                            <tr>
                                <td>
                                    Payment
                                    <div className="text-muted fs-exact-13">from account balance</div>
                                </td>
                                <td>November 2, 2020</td>
                                <td className="text-end"><Price value={50} /></td>
                            </tr>
                            <tr>
                                <td>
                                    Refund
                                    <div className="text-muted fs-exact-13">to PayPal</div>
                                </td>
                                <td>December 10, 2020</td>
                                <td className="text-end text-danger"><Price value={-325} /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="card mt-5">
                <div className="card-body px-5 py-4 d-flex align-items-center justify-content-between">
                    <h2 className="mb-0 fs-exact-18 me-4">Balance</h2>
                </div>
                <table className="sa-table">
                    <tbody className="sa-table__group">
                        <tr>
                            <td>Order Total</td>
                            <td className="text-end"><Price value={5882} /></td>
                        </tr>
                        <tr>
                            <td>Return Total</td>
                            <td className="text-end"><Price value={0} /></td>
                        </tr>
                    </tbody>
                    <tbody className="sa-table__group">
                        <tr>
                            <td>Paid by customer</td>
                            <td className="text-end"><Price value={-80} /></td>
                        </tr>
                        <tr>
                            <td>Refunded</td>
                            <td className="text-end"><Price value={0} /></td>
                        </tr>
                    </tbody>
                    <tbody>
                        <tr>
                            <td>Balance <span className="text-muted">(customer owes you)</span></td>
                            <td className="text-end"><Price value={5802} /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </>
    );

    const sidebar = (
        <>
            <div className="card">
                <div className="card-body d-flex align-items-center justify-content-between pb-0 pt-4">
                    <h2 className="fs-exact-16 mb-0">Customer</h2>
                    <a href="#" className="fs-exact-14">Edit</a>
                </div>
                <div className="card-body d-flex align-items-center pt-4">
                    <div className="sa-symbol sa-symbol--shape--circle sa-symbol--size--lg">
                        <Image src="images/customers/customer-1.jpg" size={imageSize} />
                    </div>
                    <div className="ms-3 ps-2">
                        <div className="fs-exact-14 fw-medium">
                            Jessica Moore
                        </div>
                        <div className="fs-exact-13 text-muted">
                            This is a first order
                        </div>
                    </div>
                </div>
            </div>

            <div className="card mt-5">
                <div className="card-body d-flex align-items-center justify-content-between pb-0 pt-4">
                    <h2 className="fs-exact-16 mb-0">Contact person</h2>
                    <a href="#" className="fs-exact-14">Edit</a>
                </div>
                <div className="card-body pt-4 fs-exact-14">
                    <div>
                        Jessica Moore
                    </div>
                    <div className="mt-1">
                        <a href="#">moore@example.com</a>
                    </div>
                    <div className="text-muted mt-1">
                        +38 (094) 730-24-25
                    </div>
                </div>
            </div>

            <div className="card mt-5">
                <div className="card-body d-flex align-items-center justify-content-between pb-0 pt-4">
                    <h2 className="fs-exact-16 mb-0">Shipping Address</h2>
                    <a href="#" className="fs-exact-14">Edit</a>
                </div>
                <div className="card-body pt-4 fs-exact-14">
                    Jessica Moore<br />
                    Random Federation<br />
                    115302, Moscow<br />
                    ul. Varshavskaya, 15-2-178
                </div>
            </div>

            <div className="card mt-5">
                <div className="card-body d-flex align-items-center justify-content-between pb-0 pt-4">
                    <h2 className="fs-exact-16 mb-0">Billing Address</h2>
                    <a href="#" className="fs-exact-14">Edit</a>
                </div>
                <div className="card-body pt-4 fs-exact-14">
                    Jessica Moore<br />
                    Random Federation<br />
                    115302, Moscow<br />
                    ul. Varshavskaya, 15-2-178
                </div>
            </div>
        </>
    );

    return (
        <Layout>
            <App>
                <div className="mx-sm-2 px-2 px-sm-3 px-xxl-4 pb-6">
                    <div className="container container--max--xl">
                        <PageHeader
                            title="Order #80294"
                            actions={[
                                <a key="delete" href="#" className="btn btn-secondary me-3">
                                    Delete
                                </a>,
                                <a key="edit" href="#" className="btn btn-primary">
                                    Edit
                                </a>,
                            ]}
                            breadcrumb={[
                                {title: 'Dashboard', url: url('dashboard')},
                                {title: 'Orders', url: url('orders-list')},
                                {title: 'Order #80294', url: url('order')},
                            ]}
                        />

                        <div className="sa-page-meta mb-5">
                            <div className="sa-page-meta__body">
                                <div className="sa-page-meta__list">
                                    <div className="sa-page-meta__item">October 7, 2020 at 9:08 pm</div>
                                    <div className="sa-page-meta__item">6 items</div>
                                    <div className="sa-page-meta__item">Total $5,882.00</div>
                                    <div className="sa-page-meta__item d-flex align-items-center fs-6">
                                        <span className="badge badge-sa-success me-2">Paid</span>
                                        <span className="badge badge-sa-warning me-2">Partially Fulfilled</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div
                            className="sa-entity-layout"
                            data-sa-container-query={JSON.stringify({920: 'sa-entity-layout--size--md'})}
                        >
                            <div className="sa-entity-layout__body">
                                <div className="sa-entity-layout__main">
                                    {main}
                                </div>
                                <div className="sa-entity-layout__sidebar">
                                    {sidebar}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
