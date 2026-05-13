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
                    title="Collapse"
                    subtitle="Toggle the visibility of content across your project with a few classes and our JavaScript plugins."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Collapse'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Click the buttons below to show and hide another element via class changes:
                    </p>

                    <ul>
                        <li><code>.collapse</code> hides content</li>
                        <li><code>.collapsing</code> is applied during transitions</li>
                        <li><code>.collapse.show</code> shows content</li>
                    </ul>

                    <p>
                        Generally, we recommend using a button with the <code>data-bs-target</code> attribute. While not
                        recommended from a semantic point of view, you can also use a link with
                        the <code>href</code> attribute (and a <code>role="button"</code>). In both cases,
                        the <code>data-bs-toggle="collapse"</code> is required.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <a
                                    className="btn btn-primary"
                                    data-bs-toggle="collapse"
                                    href="#collapseExample"
                                    role="button"
                                    aria-expanded="false"
                                    aria-controls="collapseExample"
                                >
                                    Link with href
                                </a>
                            </div>
                            <div className="col-auto">
                                <button
                                    className="btn btn-primary"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#collapseExample"
                                    aria-expanded="false"
                                    aria-controls="collapseExample"
                                >
                                    Button with data-bs-target
                                </button>
                            </div>
                        </div>

                        <div className="sa-collapse">
                            <div className="sa-collapse__body collapse" id="collapseExample">
                                <div className="sa-collapse__content">
                                    <div className="py-3" />
                                    <div className="card card-body">
                                        Some placeholder content for the collapse component. This panel is hidden by
                                        default but revealed when the user activates the relevant trigger.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Multiple Targets
                    </Anchor>

                    <p>
                        A <code>&lt;button&gt;</code> or <code>&lt;a&gt;</code> can show and hide multiple elements by
                        referencing them with a selector in
                        its <code>href</code> or <code>data-bs-target</code> attribute.
                        Multiple <code>&lt;button&gt;</code> or <code>&lt;a&gt;</code> can show and hide an element if
                        they each reference it with their <code>href</code> or <code>data-bs-target</code> attribute
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <a
                                    className="btn btn-primary"
                                    data-bs-toggle="collapse"
                                    href="#multiCollapseExample1"
                                    role="button"
                                    aria-expanded="false"
                                    aria-controls="multiCollapseExample1"
                                >
                                    Toggle first element
                                </a>
                            </div>
                            <div className="col-auto">
                                <button
                                    className="btn btn-primary"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#multiCollapseExample2"
                                    aria-expanded="false"
                                    aria-controls="multiCollapseExample2"
                                >
                                    Toggle second element
                                </button>
                            </div>
                            <div className="col-auto">
                                <button
                                    className="btn btn-primary"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target=".multi-collapse"
                                    aria-expanded="false"
                                    aria-controls="multiCollapseExample1 multiCollapseExample2"
                                >
                                    Toggle both elements
                                </button>
                            </div>
                        </div>

                        <div className="row">
                            <div className="col">
                                <div className="sa-collapse">
                                    <div
                                        className="sa-collapse__body collapse multi-collapse"
                                        id="multiCollapseExample1"
                                    >
                                        <div className="sa-collapse__content">
                                            <div className="py-3" />
                                            <div className="card card-body">
                                                Some placeholder content for the first collapse component of this
                                                multi-collapse example. This panel is hidden by default but revealed
                                                when the user activates the relevant trigger.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="col">
                                <div className="sa-collapse">
                                    <div
                                        className="sa-collapse__body collapse multi-collapse"
                                        id="multiCollapseExample2"
                                    >
                                        <div className="sa-collapse__content">
                                            <div className="py-3" />
                                            <div className="card card-body">
                                                Some placeholder content for the second collapse component of this
                                                multi-collapse example. This panel is hidden by default but revealed
                                                when the user activates the relevant trigger.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
