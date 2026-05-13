import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import classnames from "classnames";
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
    return (
        <Layout>
            <App>
                <div className="mx-sm-2 px-2 px-sm-3 px-xxl-4 pb-6">
                    <div className="container container--max--lg">
                        <PageHeader
                            title="General"
                            actions={[
                                <a key="reset" href="#" className="btn btn-secondary me-3">
                                    Reset
                                </a>,
                                <a key="save" href="#" className="btn btn-primary">
                                    Save
                                </a>
                            ]}
                            breadcrumb={[
                                {title: 'Dashboard', url: url('dashboard')},
                                {title: 'Settings', url: url('settings-toc')},
                                {title: 'General', url: url('settings-form')},
                            ]}
                        />

                        <Card>
                            <div className="mb-4">
                                <label htmlFor="form-settings/name" className="form-label">
                                    Store Name
                                </label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="form-settings/name"
                                    defaultValue="Stroyka"
                                />
                            </div>
                            <div className="mb-4">
                                <label htmlFor="form-settings/description" className="form-label">
                                    Store Description
                                </label>
                                <textarea
                                    className="form-control"
                                    id="form-settings/description"
                                    rows={4}
                                    defaultValue="Tools Store HTML eCommerce Template"
                                />
                            </div>
                            <div className="mb-n2">
                                <label htmlFor="form-settings/email" className="form-label">
                                    Email Address
                                </label>
                                <input
                                    type="email"
                                    className="form-control"
                                    id="form-settings/email"
                                    defaultValue="stroyka@example.com"
                                    aria-describedby="form-settings/email/help"
                                />
                                <div id="form-settings/email/help" className="form-text">
                                    The contact email address of the store administrator.
                                </div>
                            </div>
                        </Card>

                        <Card title="Measurements" className="mt-5" help="The units of measurement that will be used to determine the weight, height, width and length of goods.">
                            <div className="row g-4">
                                <div className="col-6">
                                    <label htmlFor="form-settings/weight-unit" className="form-label">
                                        Weight Unit
                                    </label>
                                    <select
                                        id="form-settings/weight-unit"
                                        className="form-select"
                                    >
                                        <option selected>kg</option>
                                        <option>g</option>
                                        <option>lbs</option>
                                        <option>oz</option>
                                    </select>
                                </div>
                                <div className="col-6">
                                    <label htmlFor="form-settings/dimensions-unit" className="form-label">
                                        Dimensions Unit
                                    </label>
                                    <select
                                        id="form-settings/dimensions-unit"
                                        className="form-select"
                                    >
                                        <option selected>m</option>
                                        <option>cm</option>
                                        <option>mm</option>
                                        <option>in</option>
                                        <option>yd</option>
                                    </select>
                                </div>
                            </div>
                        </Card>

                        <Card title="Date & Time" className="mt-5" help="Timezone, date and time format settings used in the admin panel and storefront.">
                            <div className="mb-4">
                                <label htmlFor="form-settings/timezone" className="form-label">
                                    Timezone
                                </label>
                                <select
                                    id="form-settings/timezone"
                                    className="form-select"
                                >
                                    <option>Europe/Berlin</option>
                                    <option selected>Europe/Kiev</option>
                                    <option>Europe/London</option>
                                    <option>Europe/Minsk</option>
                                    <option>Europe/Moscow</option>
                                    <option>Europe/Paris</option>
                                    <option>Europe/Warsaw</option>
                                </select>
                            </div>
                            <div className="mb-4">
                                <label htmlFor="form-settings/date-format" className="form-label">
                                    Date Format
                                </label>
                                <select
                                    id="form-settings/date-format"
                                    className="form-select"
                                >
                                    <option selected>October 19, 2020 (F j, Y)</option>
                                    <option>2020-11-08 (Y-m-d)</option>
                                    <option>11/08/2020 (m/d/Y)</option>
                                    <option>08/11/2020 (d/m/Y)</option>
                                    <option>Custom</option>
                                </select>
                            </div>
                            <div>
                                <label htmlFor="form-settings/time-format" className="form-label">
                                    Time Format
                                </label>
                                <select
                                    id="form-settings/time-format"
                                    className="form-select"
                                >
                                    <option selected>10:57 am (g:i a)</option>
                                    <option>10:57 AM (g:i A)</option>
                                    <option>10:57 (H:i)</option>
                                    <option>Custom</option>
                                </select>
                            </div>
                        </Card>

                        <Card title="Reviews & Comments" className="mt-5" help="All settings related to feedback and comments system.">
                            <div className="mb-4">
                                <div className="form-label">
                                    Enable Reviews
                                </div>
                                <div>
                                    <label className="form-check form-check-inline mb-0">
                                        <input type="radio" className="form-check-input" name="settings[reviews]" />
                                        <span className="form-check-label">No</span>
                                    </label>
                                    <label className="form-check form-check-inline mb-0">
                                        <input type="radio" className="form-check-input" name="settings[reviews]" defaultChecked />
                                        <span className="form-check-label">Yes</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <div className="form-label">
                                    Product Ratings
                                </div>
                                <div>
                                    <label className="form-check">
                                        <input type="checkbox" className="form-check-input" defaultChecked />
                                        <span className="form-check-label">Enable star rating on reviews</span>
                                    </label>
                                    <label className="form-check mb-0">
                                        <input type="checkbox" className="form-check-input" />
                                        <span className="form-check-label">Star ratings should be required, not optional</span>
                                    </label>
                                </div>
                            </div>
                        </Card>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
