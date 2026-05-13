import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import customers from '../data/customers.json';
import Image from "../components/Image";
import Price from "../components/Price";
import PageHeader from "../components/PageHeader";
import MoreButton from "../components/MoreButton";
import { url } from "../components/utils";

export default function() {
    const imageSize = 40;

    const table = (
        <table className="sa-datatables-init" data-order={'[[ 1, "asc" ]]'} data-sa-search-input="#table-search">
            <thead>
                <tr>
                    <th className="w-min" data-orderable="false">
                        <input type="checkbox" className="form-check-input m-0 fs-exact-16 d-block" aria-label="..." />
                    </th>
                    <th className="min-w-20x">Name</th>
                    <th>Registered</th>
                    <th>Country</th>
                    <th>Group</th>
                    <th>Spent</th>
                    <th className="w-min" data-orderable="false" />
                </tr>
            </thead>
            <tbody>
                {customers.map((customer, customerIdx) => (
                    <tr key={customerIdx}>
                        <td>
                            <input type="checkbox" className="form-check-input m-0 fs-exact-16 d-block" aria-label="..." />
                        </td>
                        <td>
                            <div className="d-flex align-items-center">
                                <a href={url('customer')} className="me-4">
                                    <div className="sa-symbol sa-symbol--shape--rounded sa-symbol--size--lg">
                                        <Image src={customer.avatar} size={imageSize} />
                                    </div>
                                </a>
                                <div>
                                    <a href={url('customer')} className="text-reset">{customer.name}</a>
                                    <div className="text-muted mt-n1">
                                        {customer.email}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td className="text-nowrap">{customer.createdAt}</td>
                        <td>{customer.country}</td>
                        <td>{customer.groups}</td>
                        <td><Price value={customer.spent} /></td>
                        <td><MoreButton id={`customer-context-menu-${customerIdx}`} /></td>
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
                            title="Customers"
                            actions={[
                                <a key="new_customer" href={url('customer')} className="btn btn-primary">
                                    New customer
                                </a>,
                            ]}
                            breadcrumb={[
                                {title: 'Dashboard', url: url('dashboard')},
                                {title: 'Customers', url: url('customers-list')},
                            ]}
                        />

                        <div className="card">
                            <div className="p-4">
                                <input
                                    type="text"
                                    placeholder="Start typing to search for customers"
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
