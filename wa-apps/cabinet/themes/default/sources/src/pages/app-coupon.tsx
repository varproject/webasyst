import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import classnames from 'classnames';
import PageHeader from "../components/PageHeader";
import { url } from "../components/utils";

type CardProps = {
    title?: string;
    help?: string;
    className?: string;
    body?: React.ReactNode;
    children?: React.ReactNode;
};

const Card = ({title, help, children, body, className}: CardProps) => (
    <div className={classnames('card', className)}>
        <div className="card-body p-5">
            {title && (
                <div className="mb-5">
                    <h2 className="mb-0 fs-exact-18">{title}</h2>
                    {help && <div className="mt-3 text-muted">{help}</div>}
                </div>
            )}
            {children}
        </div>
        {body}
    </div>
);

export default function() {
    const main = (
        <>
            <Card title="Basic information">
                <div className="mb-4">
                    <label htmlFor="form-coupon/code" className="form-label">
                        Code
                    </label>
                    <input type="text" className="form-control" id="form-coupon/code" placeholder="QWERTY12" />
                </div>
                <div className="mb-4">
                    <div className="form-label mb-3">
                        Type
                    </div>
                    <label className="form-check">
                        <input type="radio" className="form-check-input" name="type" defaultChecked />
                        <span className="form-check-label">Percentage</span>
                    </label>
                    <label className="form-check">
                        <input type="radio" className="form-check-input" name="type" />
                        <span className="form-check-label">Fixed amount</span>
                    </label>
                    <label className="form-check mb-0">
                        <input type="radio" className="form-check-input" name="type" />
                        <span className="form-check-label">Free shipping</span>
                    </label>
                </div>
                <div className="mb-4">
                    <label htmlFor="form-coupon/value" className="form-label">
                        Discount value
                    </label>
                    <input type="text" className="form-control" id="form-coupon/value" defaultValue="$10.00" />
                </div>
                <div className="mb-4">
                    <label htmlFor="form-coupon/limit" className="form-label">
                        Usage limit
                    </label>
                    <input type="text" className="form-control" id="form-coupon/limit" defaultValue="100" />
                </div>
                <div className="mb-n2 pt-2">
                    <label className="form-check">
                        <input type="checkbox" className="form-check-input" name="type" />
                        <span className="form-check-label">Only for registered customers</span>
                    </label>
                </div>
            </Card>
        </>
    );

    const sidebar = (
        <>
            <Card title="Status" className="w-100">
                <div className="mb-n2 mt-n3">
                    <label className="form-check">
                        <input type="radio" className="form-check-input" name="status" defaultChecked />
                        <span className="form-check-label">Enabled</span>
                    </label>
                    <label className="form-check mb-0">
                        <input type="radio" className="form-check-input" name="status"  />
                        <span className="form-check-label">Disabled</span>
                    </label>
                </div>
            </Card>

            <Card
                title="Schedule"
                help="Use these settings to limit the coupon expiration date."
                className="w-100 mt-5"
            >
                <div className="mb-4">
                    <label htmlFor="form-coupon/start-date" className="form-label">
                        Start date
                    </label>
                    <input
                        type="text"
                        className="form-control datepicker-here"
                        id="form-coupon/start-date"
                        data-auto-close="true"
                        data-language="en"
                    />
                </div>
                <div>
                    <label htmlFor="form-coupon/end-date" className="form-label">
                        End date
                    </label>
                    <input
                        type="text"
                        className="form-control datepicker-here"
                        id="form-coupon/end-date"
                        data-auto-close="true"
                        data-language="en"
                    />
                </div>
            </Card>
        </>
    );

    return (
        <Layout>
            <App>
                <div className="mx-sm-2 px-2 px-sm-3 px-xxl-4 pb-6">
                    <div className="container container--max--xl">
                        <PageHeader
                            title="Edit Coupon"
                            actions={[
                                <a key="delete" href="#" className="btn btn-secondary me-3">
                                    Delete
                                </a>,
                                <a key="edit" href="#" className="btn btn-primary">
                                    Edit
                                </a>
                            ]}
                            breadcrumb={[
                                {title: 'Dashboard', url: url('dashboard')},
                                {title: 'Coupons', url: url('coupons-list')},
                                {title: 'Edit Coupon', url: url('coupon')},
                            ]}
                        />

                        <div
                            className="sa-entity-layout"
                            data-sa-container-query={JSON.stringify({920: 'sa-entity-layout--size--md', 1100: 'sa-entity-layout--size--lg'})}
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
