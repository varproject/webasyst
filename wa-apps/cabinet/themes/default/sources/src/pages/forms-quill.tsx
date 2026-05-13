import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    const content = `
<p>Hello World!</p>
<p>Some initial <strong>bold</strong> text</p>
<p><br/></p>
`.trim();

    return (
        <Layout>
            <App>
                <Article
                    title="Quill"
                    subtitle="Modern WYSIWYG editor built for compatibility and extensibility."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Forms'},
                        {title: 'Quill'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        To initialize the quill editor, add the <code>.sa-quill-control</code> class to the
                        <code>.form-control</code> element.
                    </p>

                    <Example>
                        <textarea className="sa-quill-control form-control" rows={8} defaultValue={content} />
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
