import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    return (
        <Layout>
            <App>
                <Article
                    title="Help Text"
                    subtitle="Provide helpful instructions and hints for your form fields to quickly understand what is expected to be entered."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Forms'},
                        {title: 'Help Text'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Help text below inputs can be styled with <code>.form-text</code>. This class includes
                        <code>display: block</code> and adds some top margin for easy spacing from the inputs above.
                    </p>

                    <Example>
                        <label htmlFor="inputPassword5" className="form-label">Password</label>
                        <input
                            type="password"
                            id="inputPassword5"
                            className="form-control"
                            aria-describedby="passwordHelpBlock"
                        />
                        <div id="passwordHelpBlock" className="form-text">
                            Your password must be 8-20 characters long, contain letters and numbers, and must not
                            contain spaces, special characters, or emoji.
                        </div>
                    </Example>

                    <p>
                        Inline text can use any typical inline HTML element (be it
                        a <code>&lt;span&gt;</code>, <code>&lt;small&gt;</code>, or something else) with nothing more
                        than the <code>.form-text</code> class.
                    </p>

                    <Example>
                        <div className="row g-4 align-items-center">
                            <div className="col-auto">
                                <label htmlFor="inputPassword6" className="col-form-label">Password</label>
                            </div>
                            <div className="col-auto">
                                <input
                                    type="password"
                                    id="inputPassword6"
                                    className="form-control"
                                    aria-describedby="passwordHelpInline"
                                />
                            </div>
                            <div className="col-auto">
                                <span id="passwordHelpInline" className="form-text">Must be 8-20 characters long.</span>
                            </div>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
