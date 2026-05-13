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
                    title="Range"
                    subtitle="Consistent cross-browser and cross-device range input. Documentation and description for the corresponding control."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Forms'},
                        {title: 'Range'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Create custom <code>&lt;input type="range"&gt;</code> controls with <code>.form-range</code>.
                        The track (the background) and thumb (the value) are both styled to appear the same across
                        browsers.
                    </p>

                    <Example>
                        <label htmlFor="customRange1" className="form-label">Example range</label>
                        <input type="range" className="form-range" id="customRange1" />
                    </Example>

                    <Anchor tag="h2">
                        States
                    </Anchor>

                    <p>A range can be in several different states. Below you can see a demo of these states:</p>

                    <Example>
                        <div className="mb-4">
                            <label htmlFor="customRange2-normal" className="form-label">Normal</label>
                            <input type="range" className="form-range" id="customRange2-normal" />
                        </div>
                        <div>
                            <label htmlFor="customRange2-disabled" className="form-label">Disabled</label>
                            <input type="range" className="form-range" id="customRange2-disabled" disabled />
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
