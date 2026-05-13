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
                    title="Text Area"
                    subtitle="Text area for entering large amounts of text. Documentation and description of possible sizes and states."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Forms'},
                        {title: 'Text Area'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>Unlike a simple text field, a text area can contain multi-line text.</p>

                    <Example>
                        <textarea placeholder="Text Area" className="form-control" rows={4} />
                    </Example>

                    <Anchor tag="h2">
                        Sizing
                    </Anchor>

                    <p>
                        Set heights using classes like <code>.form-control-lg</code> and <code>.form-control-sm</code>.
                    </p>

                    <Example>
                        <textarea placeholder="Large" className="form-control form-control-lg" rows={2} />
                        <textarea placeholder="Normal" className="form-control mt-3" rows={2} />
                        <textarea placeholder="Small" className="form-control mt-3 form-control-sm" rows={2} />
                    </Example>

                    <Anchor tag="h2">
                        States
                    </Anchor>

                    <p>A text area can be in several different states. Below you can see a demo of these states:</p>

                    <Example>
                        <textarea placeholder="Normal" className="form-control" rows={2} />
                        <textarea placeholder="Readonly" readOnly className="form-control mt-3" rows={2} />
                        <textarea placeholder="Disabled" disabled className="form-control mt-3" rows={2} />
                        <textarea placeholder="Valid" className="form-control is-valid mt-3" rows={2} />
                        <textarea placeholder="Invalid" className="form-control is-invalid mt-3" rows={2} />
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
