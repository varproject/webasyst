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
                    title="File"
                    subtitle="Consistent cross-browser and cross-device file input. Documentation and description for the corresponding control."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Forms'},
                        {title: 'File'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        The file input is the most gnarly of the bunch and requires additional JavaScript if you'd like
                        to hook them up with functional <em>Choose file…</em> and selected file name text.
                    </p>

                    <Example>
                        <label htmlFor="formFile-1" className="form-label">Default file input example</label>
                        <input className="form-control" type="file" id="formFile-1" />
                    </Example>

                    <Anchor tag="h2">
                        Sizing
                    </Anchor>

                    <p>
                        The file input is the most gnarly of the bunch and requires additional JavaScript if you'd like
                        to hook them up with functional <em>Choose file…</em> and selected file name text.
                    </p>

                    <Example>
                        <div className="mb-4">
                            <label htmlFor="formFile-2-sm" className="form-label">Small file input example</label>
                            <input className="form-control form-control-sm" id="formFile-2-sm" type="file" />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="formFile-2-nl" className="form-label">Normal file input example</label>
                            <input className="form-control" id="formFile-2-nl" type="file" />
                        </div>
                        <div>
                            <label htmlFor="formFile-2-lg" className="form-label">Large file input example</label>
                            <input className="form-control form-control-lg" id="formFile-2-lg" type="file" />
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        States
                    </Anchor>

                    <p>A file can be in several different states. Below you can see a demo of these states:</p>

                    <Example>
                        <div className="mb-4">
                            <label htmlFor="formFile-3-normal" className="form-label">Normal</label>
                            <input className="form-control" id="formFile-3-normal" type="file" />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="formFile-3-disabled" className="form-label">Disabled</label>
                            <input className="form-control" id="formFile-3-disabled" type="file" disabled />
                        </div>
                        <div className="mb-4">
                            <label htmlFor="formFile-3-valid" className="form-label">Valid</label>
                            <input className="form-control is-valid" id="formFile-3-valid" type="file" />
                        </div>
                        <div>
                            <label htmlFor="formFile-3-invalid" className="form-label">Invalid</label>
                            <input className="form-control is-invalid" id="formFile-3-invalid" type="file" />
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
