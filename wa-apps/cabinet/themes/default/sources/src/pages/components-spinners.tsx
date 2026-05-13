import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    const styles = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];

    return (
        <Layout>
            <App>
                <Article
                    title="Spinners"
                    subtitle="Indicate the loading state of a component or page with Bootstrap spinners, built entirely with HTML, CSS, and no JavaScript."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Spinners'},
                    ]}
                >
                    <Anchor tag="h2">
                        Border spinner
                    </Anchor>

                    <p>Use the border spinners for a lightweight loading indicator.</p>

                    <Example>
                        <div className="spinner-border d-block" role="status">
                            <span className="visually-hidden">Loading...</span>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Colors
                    </Anchor>

                    <p>
                        The border spinner uses <code>currentColor</code> for its <code>border-color</code>, meaning you
                        can customize the color with <a href="https://getbootstrap.com/docs/5.0/utilities/colors/">text
                        color utilities</a>. You can use any of our text color utilities on the standard spinner.
                    </p>

                    <Example>
                        <div className="row g-3">
                            {styles.map((style) => (
                                <div key={style} className="col-auto">
                                    <div className={`spinner-border d-block text-${style}`} role="status">
                                        <span className="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Growing spinner
                    </Anchor>

                    <p>
                        If you don't fancy a border spinner, switch to the grow spinner. While it doesn't technically
                        spin, it does repeatedly grow!
                    </p>

                    <Example>
                        <div className="row g-3">
                            {styles.map((style) => (
                                <div key={style} className="col-auto">
                                    <div className={`spinner-grow d-block text-${style}`} role="status">
                                        <span className="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Alignment
                    </Anchor>

                    <p>
                        Use <a href="https://getbootstrap.com/docs/5.0/utilities/flex/">flexbox utilities</a>, <a
                        href="https://getbootstrap.com/docs/5.0/utilities/float/">float utilities</a>, or <a
                        href="https://getbootstrap.com/docs/5.0/content/typography/">text alignment</a> utilities to
                        place spinners exactly where you need them in any situation.
                    </p>

                    <Example>
                        <div className="d-flex align-items-center">
                            <div className="text-muted">Loading...</div>
                            <div className="spinner-border ms-auto" role="status" aria-hidden="true" />
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Size
                    </Anchor>

                    <p>
                        Add <code>.spinner-border-sm</code> and <code>.spinner-grow-sm</code> to make a smaller spinner
                        that can quickly be used within other components.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <div className="spinner-border spinner-border-sm d-block" role="status">
                                    <span className="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <div className="col-auto">
                                <div className="spinner-grow spinner-grow-sm d-block" role="status">
                                    <span className="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Buttons
                    </Anchor>

                    <p>
                        Use spinners within buttons to indicate an action is currently processing or taking place. You
                        may also swap the text out of the spinner element and utilize button text as needed.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <button className="btn btn-primary" type="button" disabled>
                                    <span className="spinner-border spinner-border-sm" role="status" aria-hidden="true" />
                                    <span className="visually-hidden">Loading...</span>
                                </button>
                            </div>
                            <div className="col-auto">
                                <button className="btn btn-primary" type="button" disabled>
                                    <span className="spinner-border spinner-border-sm me-3" role="status" aria-hidden="true" />
                                    Loading...
                                </button>
                            </div>
                            <div className="col-auto">
                                <button className="btn btn-primary" type="button" disabled>
                                    <span className="spinner-grow spinner-grow-sm" role="status" aria-hidden="true" />
                                    <span className="visually-hidden">Loading...</span>
                                </button>
                            </div>
                            <div className="col-auto">
                                <button className="btn btn-primary" type="button" disabled>
                                    <span className="spinner-grow spinner-grow-sm me-3" role="status" aria-hidden="true" />
                                    Loading...
                                </button>
                            </div>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
