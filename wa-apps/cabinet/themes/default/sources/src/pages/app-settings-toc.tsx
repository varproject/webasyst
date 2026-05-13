import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import { useSvg } from "@scompiler/0003-product/.scompiler/hooks";
import PageHeader from "../components/PageHeader";
import { url } from "../components/utils";

type Section = {
    icon: string;
    title: string;
    description: string;
};

export default function() {
    const svg = useSvg();

    const sections: Section[] = [
        {
            icon: 'feather/box',
            title: 'General',
            description: 'Mathematics began to develop at an accelerating pace',
        },
        {
            icon: 'feather/truck',
            title: 'Shipping',
            description: 'The study of quantity starts with numbers',
        },
        {
            icon: 'feather/credit-card',
            title: 'Payment',
            description: 'Mathematicians seek and use patterns',
        },
        {
            icon: 'feather/users',
            title: 'Users',
            description: 'Practical applications for what began',
        },
        {
            icon: 'feather/mail',
            title: 'Emails',
            description: 'As evidenced by tallies found on bone',
        },
        {
            icon: 'feather/dollar-sign',
            title: 'Currency',
            description: 'Three leading types of definition of mathematics today',
        },
        {
            icon: 'feather/globe',
            title: 'Languages',
            description: 'An early definition of mathematics in terms',
        },
        {
            icon: 'feather/unlock',
            title: 'Privacy',
            description: 'Mathematics arises from many different kinds of problems',
        },
        {
            icon: 'feather/percent',
            title: 'Taxes',
            description: 'Most of the mathematical notation',
        },
    ];

    return (
        <Layout>
            <App>
                <div className="mx-sm-2 px-2 px-sm-3 px-xxl-4 pb-6">
                    <div className="container container--max--xl">
                        <PageHeader
                            title="Settings"
                            breadcrumb={[
                                {title: 'Dashboard', url: url('dashboard')},
                                {title: 'Settings', url: url('settings-toc')},
                            ]}
                        />

                        <div className="row g-4">
                            {sections.map((section, sectionIdx) => (
                                <div key={sectionIdx} className="col-6 col-md-4 col-lg-3">
                                    <div className="card text-center">
                                        <a href={url('settings-form')} className="text-reset p-5 text-decoration-none sa-hover-area">
                                            <div className="fs-4 mb-4 text-muted opacity-50">
                                                {svg(section.icon)}
                                            </div>
                                            <h2 className="fs-6 fw-medium mb-3">{section.title}</h2>
                                            <div className="text-muted fs-exact-14">{section.description}</div>
                                        </a>
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
