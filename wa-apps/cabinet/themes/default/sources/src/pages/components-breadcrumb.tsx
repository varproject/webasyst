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
                    title="Breadcrumb"
                    subtitle="Indicate the current page's location within a navigational hierarchy that automatically adds separators via CSS."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Breadcrumb'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Use an ordered or unordered list with linked list items to create a minimally styled breadcrumb.
                        Use our utilities to add additional styles as desired.
                    </p>

                    <Example>
                        <nav aria-label="breadcrumb">
                            <ol className="breadcrumb breadcrumb-sa-simple mb-5">
                                <li className="breadcrumb-item active" aria-current="page">Home</li>
                            </ol>
                        </nav>

                        <nav aria-label="breadcrumb">
                            <ol className="breadcrumb breadcrumb-sa-simple mb-5">
                                <li className="breadcrumb-item"><a href="#">Home</a></li>
                                <li className="breadcrumb-item active" aria-current="page">Library</li>
                            </ol>
                        </nav>

                        <nav aria-label="breadcrumb">
                            <ol className="breadcrumb breadcrumb-sa-simple mb-0">
                                <li className="breadcrumb-item"><a href="#">Home</a></li>
                                <li className="breadcrumb-item"><a href="#">Library</a></li>
                                <li className="breadcrumb-item active" aria-current="page">Data</li>
                            </ol>
                        </nav>
                    </Example>

                    <Anchor tag="h2">
                        Dividers
                    </Anchor>

                    <p>
                        Dividers are automatically added in CSS through <code>::before</code> and <code>content</code>.
                        They can be changed by modifying a local CSS custom
                        property <code>--bs-breadcrumb-divider</code>.
                    </p>

                    <Example>
                        <nav style={{'--bs-breadcrumb-divider': '">"'} as any} aria-label="breadcrumb">
                            <ol className="breadcrumb breadcrumb-sa-simple">
                                <li className="breadcrumb-item"><a href="#">Home</a></li>
                                <li className="breadcrumb-item active" aria-current="page">Library</li>
                            </ol>
                        </nav>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
