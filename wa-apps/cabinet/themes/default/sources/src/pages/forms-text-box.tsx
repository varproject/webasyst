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
                    title="Text Box"
                    subtitle="Documentation and examples of a simple text box that is the main and most commonly used form control."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Forms'},
                        {title: 'Text Box'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <Example>
                        <input type="text" placeholder="Text Box" className="form-control" />
                    </Example>

                    <Anchor tag="h2">
                        Sizing
                    </Anchor>

                    <p>
                        Set heights using classes like <code>.form-control-lg</code> and <code>.form-control-sm</code>.
                    </p>

                    <Example>
                        <input type="text" placeholder="Large" className="form-control form-control-lg" />
                        <input type="text" placeholder="Normal" className="form-control mt-3" />
                        <input type="text" placeholder="Small" className="form-control mt-3 form-control-sm" />
                    </Example>

                    <Anchor tag="h2">
                        States
                    </Anchor>

                    <p>A text field can be in several different states. Below you can see a demo of these states:</p>

                    <Example>
                        <input type="text" placeholder="Normal" className="form-control" />
                        <input type="text" placeholder="Readonly" readOnly className="form-control mt-3" />
                        <input type="text" placeholder="Disabled" disabled className="form-control mt-3" />
                        <input type="text" placeholder="Valid" className="form-control is-valid mt-3" />
                        <input type="text" placeholder="Invalid" className="form-control is-invalid mt-3" />
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
