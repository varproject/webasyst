import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import Price from "../components/Price";
import PageHeader from "../components/PageHeader";
import MoreButton from "../components/MoreButton";
import { url } from "../components/utils";

export default function() {
    const paidStyle = (status) => (
        {
            'Yes': 'badge-sa-success',
            'No': 'badge-sa-secondary',
            'Partial': 'badge-sa-warning',
        }[status]
    );

    const statusStyle = (status) => (
        {
            'new': 'badge-sa-danger',
            'pending': 'badge-sa-primary',
            'shipped': 'badge-sa-success',
            'canceled': 'badge-sa-secondary',
        }[status]
    );

    const orders = [
        {
            number: '#3201',
            date: 'June 26, 2021',
            total: 200,
            items: 3,
            customer: 'Jessica Moore',
            status: {
                key: 'new',
                label: 'New',
            },
            paid: 'Yes',
        },
        {
            number: '#2091',
            date: 'May 15, 2021',
            total: 5023,
            items: 7,
            customer: 'Helena Garcia',
            status: {
                key: 'pending',
                label: 'Pending',
            },
            paid: 'No',
        },
        {
            number: '#1937',
            date: 'February 23, 2021',
            total: 703,
            items: 1,
            customer: 'Helena Garcia',
            status: {
                key: 'shipped',
                label: 'Shipped',
            },
            paid: 'No',
        },
        {
            number: '#1724',
            date: 'December 10, 2020',
            total: 1200,
            items: 2,
            customer: 'Ryan Ford',
            status: {
                key: 'shipped',
                label: 'Shipped',
            },
            paid: 'Partial',
        },
        {
            number: '#1603',
            date: 'August 27, 2020',
            total: 3701,
            items: 12,
            customer: 'Helena Garcia',
            status: {
                key: 'canceled',
                label: 'Canceled',
            },
            paid: 'Yes',
        },
        {
            number: '#1544',
            date: 'June 15, 2020',
            total: 127,
            items: 1,
            customer: 'Olivia Smith',
            status: {
                key: 'shipped',
                label: 'Shipped',
            },
            paid: 'Yes',
        },
        {
            number: '#1501',
            date: 'May 29, 2020',
            total: 2299,
            items: 2,
            customer: 'Kevin Smith',
            status: {
                key: 'shipped',
                label: 'Shipped',
            },
            paid: 'Yes',
        },
        {
            number: '#1429',
            date: 'May 2, 2020',
            total: 794,
            items: 1,
            customer: 'Charlotte Jones',
            status: {
                key: 'shipped',
                label: 'Shipped',
            },
            paid: 'Partial',
        },
        {
            number: '#1373',
            date: 'March 9, 2020',
            total: 27899,
            items: 28,
            customer: 'Jacob Lee',
            status: {
                key: 'pending',
                label: 'Pending',
            },
            paid: 'Yes',
        },
        {
            number: '#1288',
            date: 'February 12, 2020',
            total: 4302,
            items: 4,
            customer: 'Isabel Williams',
            status: {
                key: 'shipped',
                label: 'Shipped',
            },
            paid: 'Yes',
        },
        {
            number: '#1108',
            date: 'January 25, 2020',
            total: 239,
            items: 1,
            customer: 'Anna Wilson',
            status: {
                key: 'shipped',
                label: 'Shipped',
            },
            paid: 'Yes',
        },
        {
            number: '#1002',
            date: 'January 3, 2020',
            total: 5103,
            items: 7,
            customer: 'Adam Taylor',
            status: {
                key: 'canceled',
                label: 'Canceled',
            },
            paid: 'No',
        },
    ];

    const table = (
        <table className="sa-datatables-init text-nowrap" data-order={'[[ 1, "desc" ]]'} data-sa-search-input="#table-search">
            <thead>
                <tr>
                    <th className="w-min" data-orderable="false">
                        <input type="checkbox" className="form-check-input m-0 fs-exact-16 d-block" aria-label="..." />
                    </th>
                    <th>Number</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th className="w-min" data-orderable="false" />
                </tr>
            </thead>
            <tbody>
                {orders.map((order, orderIdx) => (
                    <tr key={orderIdx}>
                        <td>
                            <input type="checkbox" className="form-check-input m-0 fs-exact-16 d-block" aria-label="..." />
                        </td>
                        <td>
                            <a href={url('order')} className="text-reset">{order.number}</a>
                        </td>
                        <td>{order.date}</td>
                        <td><a href={url('customer')} className="text-reset">{order.customer}</a></td>
                        <td>
                            <div className="d-flex fs-6">
                                <div className={`badge ${paidStyle(order.paid)}`}>
                                    {order.paid}
                                </div>
                            </div>
                        </td>
                        <td>
                            <div className="d-flex fs-6">
                                <div className={`badge ${statusStyle(order.status.key)}`}>
                                    {order.status.label}
                                </div>
                            </div>
                        </td>
                        <td>{order.items} items</td>
                        <td><Price value={order.total} /></td>
                        <td><MoreButton id={`order-context-menu-${orderIdx}`} /></td>
                    </tr>
                ))}
            </tbody>
        </table>
    );

    return (
        <Layout>
            <App>
                <div className="mx-sm-2 px-2 px-sm-3 px-xxl-4 pb-6">
                    <div className="container">
                        <PageHeader
                            title="Orders"
                            actions={[
                                <a key="new_order" href={url('order')} className="btn btn-primary">
                                    New order
                                </a>,
                            ]}
                            breadcrumb={[
                                {title: 'Dashboard', url: url('dashboard')},
                                {title: 'Orders', url: url('orders-list')},
                            ]}
                        />

                        <div className="card">
                            <div className="p-4">
                                <input
                                    type="text"
                                    placeholder="Start typing to search for orders"
                                    className="form-control form-control--search mx-auto"
                                    id="table-search"
                                />
                            </div>

                            <div className="sa-divider" />

                            {table}
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
