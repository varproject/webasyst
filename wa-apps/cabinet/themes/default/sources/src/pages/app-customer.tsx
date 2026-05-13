import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import { useSvg } from '@scompiler/0003-product/.scompiler/hooks';
import Image from "../components/Image";
import PageHeader from "../components/PageHeader";
import MoreButton from "../components/MoreButton";
import { url } from "../components/utils";

export default function() {
    const svg = useSvg();
    const imageSize = 16 * 6;

    const orders = [
        {number: '#80294', data: 'Today at 6:10 pm', status: 'Pending', items: '4 items', total: '$320.00'},
        {number: '#63736', data: 'May 15, 2019', status: 'Completed', items: '7 items', total: '$2,574.31'},
        {number: '#63501', data: 'January 7, 2019', status: 'Completed', items: '1 items', total: '$34.00'},
        {number: '#40278', data: 'October 19, 2018', status: 'Completed', items: '2 items', total: '$704.00'},
    ];

    const addresses = [
        {
            name: 'Jessica Moore',
            address: 'Random Federation 115302, Moscow ul. Varshavskaya, 15-2-178',
        },
        {
            name: 'Neptune Saturnov',
            address: 'Earth 4b4f53, MarsGrad Sun Orbit, 43.3241-85.239',
        },
    ];

    const main = (
        <>
            <div className="sa-card-area">
                <textarea className="sa-card-area__area" rows={2} placeholder="Notes about customer" />
                <div className="sa-card-area__card">
                    {svg('feather/edit')}
                </div>
            </div>

            <div className="card mt-5">
                <div className="card-body px-5 py-4 d-flex align-items-center justify-content-between">
                    <h2 className="mb-0 fs-exact-18 me-4">Orders</h2>
                    <div className="text-muted fs-exact-14 text-end">
                        Total spent $34,980.34 on 7 orders
                    </div>
                </div>
                <div className="table-responsive">
                    <table className="sa-table text-nowrap">
                        <tbody>
                            {orders.map((order, orderIdx) => (
                                <tr key={orderIdx}>
                                    <td>
                                        <a href={url('order')}>{order.number}</a>
                                    </td>
                                    <td>{order.data}</td>
                                    <td>{order.status}</td>
                                    <td>{order.items}</td>
                                    <td>{order.total}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="sa-divider" />
                <div className="px-5 py-4 text-center">
                    <a href={url('orders-list')}>View all 7 orders</a>
                </div>
            </div>

            <div className="card mt-5">
                <div className="card-body px-5 py-4 d-flex align-items-center justify-content-between">
                    <h2 className="mb-0 fs-exact-18 me-4">Addresses</h2>
                    <div className="text-muted fs-exact-14">
                        <a href="#">New address</a>
                    </div>
                </div>

                {addresses.map((address, addressIdx) => (
                    <React.Fragment key={addressIdx}>
                        <div className="sa-divider" />
                        <div className="px-5 py-3 my-2 d-flex align-items-center justify-content-between">
                            <div>
                                <div>{address.name}</div>
                                <div className="text-muted fs-exact-14 mt-1">
                                    {address.address}
                                </div>
                            </div>
                            <div>
                                <MoreButton id={`address-context-menu-${addressIdx}`} />
                            </div>
                        </div>
                    </React.Fragment>
                ))}
            </div>
        </>
    );

    const sidebar = (
        <>
            <div className="card">
                <div className="card-body d-flex flex-column align-items-center">

                    <div className="pt-3">
                        <div className="sa-symbol sa-symbol--shape--circle" style={{'--sa-symbol--size': '6rem'} as any}>
                            <Image src="images/customers/customer-1.jpg" size={imageSize} />
                        </div>
                    </div>

                    <div className="text-center mt-4">
                        <div className="fs-exact-16 fw-medium">Jessica Moore</div>
                        <div className="fs-exact-13 text-muted">
                            <div className="mt-1"><a href="">jessica-moore@example.com</a></div>
                            <div className="mt-1">+38 (094) 730-24-25</div>
                        </div>
                    </div>

                    <div className="sa-divider my-5" />

                    <div className="w-100">
                        <dl className="list-unstyled m-0">
                            <dt className="fs-exact-14 fw-medium">Last Order</dt>
                            <dd className="fs-exact-13 text-muted mb-0 mt-1">7 days ago – <a href={url('order')}>#80294</a></dd>
                        </dl>
                        <dl className="list-unstyled m-0 mt-4">
                            <dt className="fs-exact-14 fw-medium">Average Order Value</dt>
                            <dd className="fs-exact-13 text-muted mb-0 mt-1">$574.00</dd>
                        </dl>
                        <dl className="list-unstyled m-0 mt-4">
                            <dt className="fs-exact-14 fw-medium">Registered</dt>
                            <dd className="fs-exact-13 text-muted mb-0 mt-1">2 months ago</dd>
                        </dl>
                        <dl className="list-unstyled m-0 mt-4">
                            <dt className="fs-exact-14 fw-medium">Email Marketing</dt>
                            <dd className="fs-exact-13 text-muted mb-0 mt-1">Subscribed</dd>
                        </dl>
                    </div>
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
                            title="Jessica Moore"
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
                                {title: 'Customers', url: url('customers-list')},
                                {title: 'Jessica Moore', url: url('customer')},
                            ]}
                        />
                        <div
                            className="sa-entity-layout"
                            data-sa-container-query={JSON.stringify({920: 'sa-entity-layout--size--md'})}
                        >
                            <div className="sa-entity-layout__body">
                                <div className="sa-entity-layout__sidebar">
                                    {sidebar}
                                </div>
                                <div className="sa-entity-layout__main">
                                    {main}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
