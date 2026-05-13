import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import MoreButton from "../components/MoreButton";
import PageHeader from "../components/PageHeader";
import { url } from "../components/utils";

export default function() {
    const statusStyle = (status) => (
        {
            'visible': 'badge-sa-success',
            'hidden': 'badge-sa-secondary',
            'scheduled': 'badge-sa-primary',
            'restrict': 'badge-sa-danger',
        }[status]
    );

    const categories = [
        {
            "name": "Headlights & Lighting",
            "items": 3,
            "status": {
                key: 'visible',
                label: 'Visible',
            },
        },
        {
            "name": "Interior Parts",
            "items": 15,
            "status": {
                key: 'scheduled',
                label: '8 March',
            },
        },
        {
            "name": "Floor Mats",
            "items": 0,
            "status": {
                key: 'visible',
                label: 'Visible',
            },
        },
        {
            "name": "Gauges",
            "items": 0,
            "status": {
                key: 'visible',
                label: 'Visible',
            },
        },
        {
            "name": "Consoles & Organizers",
            "items": 0,
            "status": {
                key: 'visible',
                label: 'Visible',
            },
        },
        {
            "name": "Mobile Electronics",
            "items": 0,
            "status": {
                key: 'visible',
                label: 'Visible',
            },
        },
        {
            "name": "Steering Wheels",
            "items": 0,
            "status": {
                key: 'visible',
                label: 'Visible',
            },
        },
        {
            "name": "Cargo Accessories",
            "items": 0,
            "status": {
                key: 'visible',
                label: 'Visible',
            },
        },
        {
            "name": "Engine & Drivetrain",
            "items": 0,
            "status": {
                key: 'hidden',
                label: 'Hidden',
            },
        },
        {
            "name": "Repair Manuals",
            "items": 45,
            "status": {
                key: 'visible',
                label: 'Visible',
            },
        },
        {
            "name": "Suspension",
            "items": 28,
            "status": {
                key: 'visible',
                label: 'Visible',
            },
        },
        {
            "name": "Fuel Systems",
            "items": 11,
            "status": {
                key: 'restrict',
                label: 'For premium',
            },
        },
        {
            "name": "Air Filters",
            "items": 0,
            "status": {
                key: 'hidden',
                label: 'Hidden',
            },
        }
    ];

    const table = (
        <table className="sa-datatables-init" data-order={'[[ 1, "asc" ]]'} data-sa-search-input="#table-search">
            <thead>
                <tr>
                    <th className="w-min" data-orderable="false">
                        <input type="checkbox" className="form-check-input m-0 fs-exact-16 d-block" aria-label="..." />
                    </th>
                    <th className="min-w-15x">Name</th>
                    <th>Items</th>
                    <th>Visibility</th>
                    <th className="w-min" data-orderable="false" />
                </tr>
            </thead>
            <tbody>
                {categories.map((category, categoryIdx) => (
                    <tr key={categoryIdx}>
                        <td>
                            <input type="checkbox" className="form-check-input m-0 fs-exact-16 d-block" aria-label="..." />
                        </td>
                        <td>
                            <a href={url('category')} className="text-reset">{category.name}</a>
                        </td>
                        <td>
                            {category.items}
                        </td>
                        <td>
                            {category.status && (
                                <div className={`badge ${statusStyle(category.status.key)}`}>
                                    {category.status.label}
                                </div>
                            )}
                            {!category.status && (
                                <div className="sa-dash" role="presentation" />
                            )}
                        </td>
                        <td>
                            <MoreButton id={`category-context-menu-${categoryIdx}`} />
                        </td>
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
                            title="Categories"
                            actions={[
                                <a key="new_category" href={url('category')} className="btn btn-primary">
                                    New category
                                </a>,
                            ]}
                            breadcrumb={[
                                {title: 'Dashboard', url: url('dashboard')},
                                {title: 'Categories', url: url('categories-list')},
                            ]}
                        />

                        <div className="card">
                            <div className="p-4">
                                <input
                                    type="text"
                                    placeholder="Start typing to search for categories"
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
