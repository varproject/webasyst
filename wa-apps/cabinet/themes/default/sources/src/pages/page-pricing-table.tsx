import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import { useSvg } from '@scompiler/0003-product/.scompiler/hooks';
import classNames from "classNames";

export default function() {
    const svg = useSvg();
    const plans = [
        {
            name: 'Starter',
            price: '$0',
            description: 'For exploring the platform',
            products: 10,
            members: 1,
            disk: '50MB',
            support: false,
            editor: false,
            manager: false,
        },
        {
            name: 'Standard',
            price: '$49',
            description: 'For small business',
            products: 100,
            members: 5,
            disk: '500MB',
            support: true,
            editor: true,
            manager: false,
        },
        {
            name: 'Premium',
            price: '$249',
            description: 'For medium business',
            products: 500,
            members: 15,
            disk: '1GB',
            support: true,
            editor: true,
            manager: true,
        },
    ];

    const feature = (title, included) => (
        <li className={classNames('sa-price-card__feature', {'sa-price-card__feature--included': included, 'sa-price-card__feature--not-included': !included})}>
            <div className="sa-price-card__feature-icon">
                {svg('stroyka/check-16')}
            </div>
            <div className="sa-price-card__feature-title">{title}</div>
        </li>
    );

    return (
        <Layout>
            <App>
                <div className="py-5 py-md-6 my-2 px-5">
                    <div className="sa-hero-header">
                        <div className="sa-hero-header__title">
                            <h1>Choose Your Plan</h1>
                        </div>
                        <div className="sa-hero-header__subtitle">
                            Choose the features and functionality your team needs today.<br/>
                            Easily upgrade as your company grows.
                        </div>
                        <div className="sa-hero-header__controls">
                            <div className="sa-switch mx-auto">
                                <div className="sa-switch__body">
                                    <input
                                        id="billing-period-year"
                                        type="radio"
                                        name="billing_period"
                                        value="year"
                                        defaultChecked
                                    />
                                    <label htmlFor="billing-period-year">Yearly billing</label>
                                    <input
                                        id="billing-period-month"
                                        type="radio"
                                        name="billing_period"
                                        value="month"
                                    />
                                    <label htmlFor="billing-period-month">Monthly billing</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="pb-6 mb-2 px-3 px-sm-4">
                    <div className="container container--max--lg">
                        <div className="row align-items-end g-4 g-sm-5">
                            {plans.map((plan, planIdx) => (
                                <div key={planIdx} className="col-12 col-lg-4 d-flex justify-content-center">
                                    <div className="sa-price-card card flex-grow-1 mx-sm-2">
                                        {planIdx === 1 && (
                                            <div className="sa-price-card__badge">
                                                Most Popular
                                            </div>
                                        )}
                                        <div className="card-body">
                                            <div className="sa-price-card__title">
                                                {plan.name}
                                            </div>
                                            <div className="sa-price-card__subtitle">
                                                {plan.description}
                                            </div>
                                            <div className="sa-price-card__price">
                                                <div className="sa-price-card__price-value">{plan.price}</div>
                                                <div className="sa-price-card__price-period">/ Month</div>
                                            </div>
                                            <div className="sa-price-card__button">
                                                <button type="button" className="btn btn-primary">Get Started</button>
                                            </div>
                                        </div>
                                        <div className="sa-divider sa-divider--has-text mt-3">
                                            <div className="sa-divider__text">Plan includes</div>
                                        </div>
                                        <div className="card-body">
                                            <ul className="sa-price-card__features-list">
                                                {feature(<><strong>{plan.products}</strong> Products</>, true)}
                                                {feature(<><strong>{plan.members}</strong> Team Members</>, true)}
                                                {feature(<><strong>{plan.disk}</strong> Disk Space</>, true)}
                                                {feature(<>24/7 support</>, plan.support)}
                                                {feature(<>Theme Editor</>, plan.editor)}
                                                {feature(<>Personal Manager</>, plan.manager)}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
