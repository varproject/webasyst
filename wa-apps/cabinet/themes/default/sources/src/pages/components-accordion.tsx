import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import classNames from 'classNames';
import { repeat, url } from "../components/utils";

export default function() {
    return (
        <Layout>
            <App>
                <Article
                    title="Accordion"
                    subtitle="Accordion is useful when you need to switch between hiding and showing a lot of content."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Accordion'},
                    ]}
                >
                    <Anchor id="article-basic-example" tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Basic layout when all accordion parts are inside one card.
                    </p>

                    <Example>
                        <div className="accordion card" id="accordion-1">
                            {repeat(3, (idx) => (
                                <React.Fragment key={idx}>
                                    {idx !== 0 && <div className="sa-divider" />}
                                    <div className="accordion-item">
                                        <h2 className="accordion-header" id={`accordion-1-item-${idx}-header`}>
                                            <button
                                                className={classNames('accordion-button sa-hover-area', {collapsed: idx !== 0})}
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target={`#accordion-1-item-${idx}`}
                                                aria-expanded={idx === 0}
                                                aria-controls={`accordion-1-item-${idx}`}
                                            >
                                                <span className="accordion-sa-icon" />
                                                Accordion Item #{idx + 1}
                                            </button>
                                        </h2>
                                        <div
                                            id={`accordion-1-item-${idx}`}
                                            className={classNames('accordion-collapse', 'collapse', {show: idx === 0} )}
                                            data-bs-parent="#accordion-1"
                                            aria-labelledby={`accordion-1-item-${idx}-header`}
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
                                </React.Fragment>
                            ))}
                        </div>
                    </Example>

                    <Anchor id="article-insular-layout" tag="h2">
                        Insular Layout
                    </Anchor>

                    <p>
                        Add <code>.card</code> to each <code>.accordion-item</code> and remove all <code>.sa-divider</code> to get an "Insular" layout.
                    </p>

                    <Example>
                        <div className="accordion" id="accordion-2">
                            {repeat(3, (idx) => (
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
                                            Accordion Item #{idx + 1}
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
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
