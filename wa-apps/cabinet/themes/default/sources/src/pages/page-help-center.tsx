import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import classNames from "classNames";

export default function() {
    const sections = [
        {
            'title': 'Getting started',
            'links': [
                'How do I purchase an item?',
                'View and Download invoices',
                'Do I need a Regular License?',
                'How to Download your items',
                'Licenses Overview',
            ],
        },
        {
            'title': 'Popular articles',
            'links': [
                'Where Is My Purchase Code?',
                'Bundled Plugins',
                'How to contact an author',
                'What is Item Support?',
                'I\'ve Forgotten My Username',
            ],
        },
        {
            'title': 'Settings',
            'links': [
                'How do I change the currency?',
                'Basic store settings',
                'Setting the units of measure',
                'How do I change the language?',
                'Date and time settings',
            ],
        },
        {
            'title': 'Licenses',
            'links': [
                'How do I choose a license?',
                'What are the license types?',
                'Term or perpetual license?',
                'Extended license',
                'Where can I download the certificate?',
            ],
        },
        {
            'title': 'Javascript',
            'links': [
                'List of third party scripts',
                'Where is the API key configured?',
                'Making AJAX requests',
                'Setting up search suggestions',
                'Using Bootstrap Components',
            ],
        },
        {
            'title': 'Customization',
            'links': [
                'How to change logo?',
                'How to change font?',
                'How to change color scheme?',
                'How to remove copyright notice?',
                'How to add a page?',
            ],
        },
    ];

    return (
        <Layout>
            <App>
                <div className="py-5 py-md-6 my-2 px-5">
                    <div className="sa-hero-header">
                        <div className="sa-hero-header__title">
                            <h1>How we can help?</h1>
                        </div>
                        <div className="sa-hero-header__controls">
                            <input type="text" placeholder="Search over FAQ" className="form-control form-control--search-filled mx-auto max-w-25x" />
                        </div>
                    </div>
                </div>

                <div className="container container--max--xl pb-6">
                    <div className="row g-5 row-cols-1 row-cols-sm-2 row-cols-md-3">
                        {sections.map((section, sectionIdx) => (
                            <div key={sectionIdx} className="col d-flex">
                                <div className="card w-100">
                                    <div className="card-body p-5">
                                        <h2 className="fs-exact-17">{section.title}</h2>

                                        <ul className="list-unstyled my-4">
                                            {section.links.map((link, linkIdx) => (
                                                <li key={linkIdx} className={classNames({'pt-2': linkIdx > 0})}>
                                                    <a href="#" className="text-muted">{link}</a>
                                                </li>
                                            ))}
                                        </ul>

                                        <div className="mb-n2">
                                            <a href="">See all articles (17)</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </App>
        </Layout>
    );
}
