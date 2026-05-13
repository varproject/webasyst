import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import classNames from "classnames";

export default function() {
    const questions = [
        'How does it work?',
        'Do I need a Regular License or an Extended License?',
        'What is Item Support?',
        'How to download your Item?',
        'How to contact before purchase?',
    ];

    return (
        <Layout>
            <App>
                <div className="py-5 py-md-6 my-2 px-5">
                    <div className="sa-hero-header">
                        <div className="sa-hero-header__title">
                            <h1>Frequently Asked Questions</h1>
                        </div>
                        <div className="sa-hero-header__subtitle">
                            Choose the features and functionality your team needs today.<br />
                            Easily upgrade as your company grows.
                        </div>
                        <div className="sa-hero-header__controls">
                            <input type="text" placeholder="Search over FAQ" className="form-control form-control--search-filled mx-auto max-w-25x" />
                        </div>
                    </div>
                </div>

                <div className="container container--max--md">
                    <div className="accordion" id="accordion-2">
                        {questions.map((question, idx) => (
                            <div key={idx} className={classNames('accordion-item', 'card', {'mt-4': idx !== 0})}>
                                <h2 className="accordion-header" id={`accordion-2-item-${idx}-header`}>
                                    <button
                                        className={classNames('accordion-button sa-hover-area', {collapsed: idx !== 0})}
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target={`#accordion-2-item-${idx}`}
                                        aria-expanded={idx === 0}
                                        aria-controls={`accordion-2-item-${idx}`}
                                    >
                                        <span className="accordion-sa-icon" />
                                        {question}
                                    </button>
                                </h2>
                                <div
                                    id={`accordion-2-item-${idx}`}
                                    className={classNames('accordion-collapse', 'collapse', {show: idx === 0} )}
                                    data-bs-parent="#accordion-2"
                                    aria-labelledby={`accordion-2-item-${idx}-header`}
                                >
                                    <div className="accordion-body">
                                        Historically, philosophy encompassed all bodies of knowledge and a
                                        practitioner was known as a philosopher. From the time of Ancient Greek
                                        philosopher Aristotle to the 19th century, "natural philosophy" encompassed
                                        astronomy, medicine, and physics. For example, Newton's 1687 Mathematical
                                        Principles of Natural Philosophy later became classified as a book of
                                        physics.
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="py-6 text-center">
                        <h2 className="h4">Still Have A Questions?</h2>
                        <div className="pt-2 text-muted">
                            We will be happy to answer any questions you may have.
                        </div>
                        <div className="pt-5">
                            <a href="#" className="btn btn-primary">Contact Us</a>
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
