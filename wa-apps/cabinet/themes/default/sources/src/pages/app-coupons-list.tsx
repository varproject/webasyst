import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import coupons from '../data/coupons.json';
import PageHeader from "../components/PageHeader";
import MoreButton from "../components/MoreButton";
import { url } from "../components/utils";

export default function() {
    const statusStyle = (status) => (
        {
            'Enabled': 'badge-sa-success',
            'Planned': 'badge-sa-primary',
            'Finished': 'badge-sa-info',
            'canceled': 'badge-sa-secondary',
        }[status]
    );

    const table = (
        <table className="sa-datatables-init text-nowrap" data-order={'[[ 1, "asc" ]]'} data-sa-search-input="#table-search">
            <thead>
                <tr>
                    <th className="w-min" data-orderable="false">
                        <input type="checkbox" className="form-check-input m-0 fs-exact-16 d-block" aria-label="..." />
                    </th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Discount</th>
                    <th>Status</th>
                    <th>Start date</th>
                    <th>End date</th>
                    <th className="w-min" data-orderable="false" />
                </tr>
            </thead>
            <tbody>
                {coupons.map((coupon, couponIdx) => (
                    <tr key={couponIdx}>
                        <td>
                            <input type="checkbox" className="form-check-input m-0 fs-exact-16 d-block" aria-label="..." />
                        </td>
                        <td>
                            <a href={url('coupon')} className="text-reset">{coupon.code}</a>
                        </td>
                        <td>{coupon.type}</td>
                        <td>{coupon.discount}</td>
                        <td>
                            <div className="d-flex fs-16">
                                <div className={`badge badge-sa-pill ${statusStyle(coupon.status)}`}>
                                    {coupon.status}
                                </div>
                            </div>
                        </td>
                        <td>{coupon.startDate}</td>
                        <td>
                            {coupon.endDate}
                            {!coupon.endDate && <div className="sa-dash" />}
                        </td>
                        <td><MoreButton id={`coupon-context-menu-${couponIdx}`} /></td>
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
                            title="Coupons"
                            actions={[
                                <a key="new_coupon" href={url('coupon')} className="btn btn-primary">
                                    New coupon
                                </a>,
                            ]}
                            breadcrumb={[
                                {title: 'Dashboard', url: url('dashboard')},
                                {title: 'Coupons', url: url('coupons-list')},
                            ]}
                        />

                        <div className="card">
                            <div className="p-4">
                                <input
                                    type="text"
                                    placeholder="Start typing to search for coupons"
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
