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
                    title="Select"
                    subtitle="A control for choosing from predefined options. Documentation and description for the corresponding element."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Forms'},
                        {title: 'Select'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Custom <code>&lt;select&gt;</code> menus need only a custom class, <code>.form-select</code> to
                        trigger the custom styles. Custom styles are limited to the <code>&lt;select&gt;</code>'s initial
                        appearance and cannot modify the <code>&lt;option&gt;</code>s due to browser limitations.
                    </p>

                    <Example>
                        <select className="form-select">
                            <option selected>Open this select menu</option>
                            <option value="1">One</option>
                            <option value="2">Two</option>
                            <option value="3">Three</option>
                        </select>
                    </Example>

                    <Anchor tag="h2">
                        Sizing
                    </Anchor>

                    <p>
                        Set heights using classes like <code>.form-control-lg</code> and <code>.form-control-sm</code>.
                    </p>

                    <Example>
                        <select className="form-select form-select-lg">
                            <option selected>Large</option>
                        </select>
                        <select className="form-select mt-3">
                            <option selected>Normal</option>
                        </select>
                        <select className="form-select mt-3 form-select-sm">
                            <option selected>Small</option>
                        </select>
                    </Example>

                    <Anchor tag="h2">
                        States
                    </Anchor>

                    <p>A select can be in several different states. Below you can see a demo of these states:</p>

                    <Example>
                        <select className="form-select">
                            <option selected>Normal</option>
                        </select>
                        <select className="form-select mt-3" disabled>
                            <option selected>Disabled</option>
                        </select>
                        <select className="form-select mt-3 is-valid">
                            <option selected>Valid</option>
                        </select>
                        <select className="form-select mt-3 is-invalid">
                            <option selected>Invalid</option>
                        </select>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
