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
                    title="Tooltips"
                    subtitle="Documentation and examples for adding custom Bootstrap tooltips with CSS and JavaScript using CSS3 for animations and data-bs-attributes for local title storage."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Tooltips'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>Hover over the links below to see tooltips:</p>

                    <Example>
                        <div className="card">
                            <div className="card-body">
                                Placeholder text to demonstrate some <a href="#" data-bs-toggle="tooltip" title="Default tooltip">inline links</a> with tooltips. This is now just filler, no killer. Content placed here just to mimic the presence of <a href="#" data-bs-toggle="tooltip" title="Another tooltip">real text</a>. And all that just to give you an idea of how tooltips would look when used in real-world situations. So hopefully you've now seen how <a href="#" data-bs-toggle="tooltip" title="Another one here too">these tooltips on links</a> can work in practice, once you use them on <a href="#" data-bs-toggle="tooltip" title="The last tip!">your own</a> site or project.
                            </div>
                        </div>
                    </Example>

                    <p>
                        Hover over the buttons below to see the four tooltips directions: top, right, bottom, and left.
                        Directions are mirrored when using Bootstrap in RTL.
                    </p>

                    <Example>
                        <div className="row g-3">
                            {['left', 'top', 'bottom', 'right'].map((direction) => (
                                <div key={direction} className="col-auto">
                                    <button
                                        type="button"
                                        className="btn btn-primary"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement={direction}
                                        title={`Tooltip on ${direction}`}
                                    >
                                        Tooltip on {direction}
                                    </button>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <p>And with custom HTML added:</p>

                    <Example>
                        <button
                            type="button"
                            className="btn btn-secondary"
                            data-bs-toggle="tooltip"
                            data-bs-html="true"
                            title="<em>Tooltip</em> <u>with</u> <b>HTML</b>"
                        >
                            Tooltip with HTML
                        </button>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
