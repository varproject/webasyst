import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import PageHeader from "../components/PageHeader";
import { url } from "../components/utils";

export default function() {
    const rows = [
        {
            browser: 'Chrome',
            users: '10,987',
            sessions: '3,843',
            bounceRate: '42.69%',
            orders: '7',
            total: '$2,756',
        },
        {
            browser: 'Firefox',
            users: '1,152',
            sessions: '405',
            bounceRate: '39.60%',
            orders: '12',
            total: '$296',
        },
        {
            browser: 'Safari',
            users: '699',
            sessions: '253',
            bounceRate: '47.36%',
            orders: '5',
            total: '$571',
        },
        {
            browser: 'Edge',
            users: '370',
            sessions: '29',
            bounceRate: '34.33%',
            orders: '2',
            total: '$64',
        },
        {
            browser: 'Opera',
            users: '27',
            sessions: '4',
            bounceRate: '12.76%',
            orders: '3',
            total: '$103',
        },
    ];

    const table = (
        <table className="sa-datatables-init text-nowrap" data-order={'[[ 2, "desc" ]]'}>
            <thead>
                <tr>
                    <th className="w-min" data-orderable="false">
                        <input type="checkbox" className="form-check-input" aria-label="..." />
                    </th>
                    <th>Browser</th>
                    <th>Users</th>
                    <th>Sessions</th>
                    <th>Bounce Rate</th>
                    <th>Orders</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                {rows.map((row, rowIdx) => (
                    <tr key={rowIdx}>
                        <td>
                            <input type="checkbox" className="form-check-input" aria-label="..." />
                        </td>
                        <td>{row.browser}</td>
                        <td>{row.users}</td>
                        <td>{row.sessions}</td>
                        <td>{row.bounceRate}</td>
                        <td>{row.orders}</td>
                        <td>{row.total}</td>
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
                            title="Analytics"
                            actions={[
                                <a key="export" href="#" className="btn btn-secondary me-3">
                                    Export
                                </a>,
                                <a key="save" href="#" className="btn btn-primary">
                                    Save
                                </a>
                            ]}
                            breadcrumb={[
                                {title: 'Dashboard', url: url('dashboard')},
                                {title: 'Analytics', url: url('analytics')},
                            ]}
                        />

                        <div className="card">
                            <div className="card-body">
                                <div className="sa-chart-toolbar mb-5 mt-n2">
                                    <div className="sa-chart-toolbar__body">
                                        <div className="sa-chart-toolbar__item me-auto">
                                            <label htmlFor="analytics/date-range" className="sa-chart-toolbar__item-label">
                                                Date range
                                            </label>
                                            <select
                                                id="analytics/date-range"
                                                className="form-select form-select-sm sa-chart-toolbar__item-select"
                                            >
                                                <option>Current month</option>
                                                <option selected>Current year</option>
                                                <option>Last year</option>
                                                <option>[Custom]</option>
                                            </select>
                                            <div className="sa-chart-toolbar__item-range">
                                                <input
                                                    type="text"
                                                    className="form-control form-control-sm datepicker-here"
                                                    placeholder="Start date"
                                                    data-auto-close="true"
                                                    data-language="en"
                                                    aria-label="Datepicker"
                                                />
                                                <div className="text-muted mx-3">
                                                    <div className="sa-dash sa-dash--size--small" />
                                                </div>
                                                <input
                                                    type="text"
                                                    className="form-control form-control-sm datepicker-here"
                                                    placeholder="End date"
                                                    data-auto-close="true"
                                                    data-language="en"
                                                    aria-label="Datepicker"
                                                />
                                            </div>
                                        </div>
                                        <div className="sa-chart-toolbar__item">
                                            <label htmlFor="analytics/group-by" className="sa-chart-toolbar__item-label">
                                                Group by
                                            </label>
                                            <select
                                                id="analytics/group-by"
                                                className="form-select form-select-sm"
                                            >
                                                <option selected>Day</option>
                                                <option>Month</option>
                                                <option>Year</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    className="sa-box"
                                    data-sa-container-query={JSON.stringify({
                                        320: 'sa-box--aspect-ratio--3:2',
                                        640: 'sa-box--aspect-ratio--2:1',
                                        1080: 'sa-box--aspect-ratio--3:1',
                                    })}
                                    data-sa-container-query-mode="bigger"
                                >
                                    <div className="sa-box__body">
                                        <div className="sa-box__container">
                                            <canvas id="example-chart-js-analytics" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="card mt-5">
                            <div className="p-4">
                                <div className="row g-3">
                                    <div className="col">
                                        <input type="text" placeholder="Start typing to search" className="form-control form-control--search" />
                                    </div>
                                    <div className="col-auto">
                                        <button type="button" className="btn btn-secondary" disabled>Plot Rows</button>
                                    </div>
                                </div>
                            </div>

                            <div className="sa-divider" />

                            <div className="table-responsive">
                                {table}
                            </div>
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
