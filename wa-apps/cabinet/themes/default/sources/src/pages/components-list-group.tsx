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
                    title="List Group"
                    subtitle="List groups are a flexible and powerful component for displaying a series of content. Modify and extend them to support just about any content within."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'List Group'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        The most basic list group is an unordered list with list items and the proper classes. Build
                        upon it with the options that follow, or with your own CSS as needed.
                    </p>

                    <Example>
                        <ul className="list-group">
                            <li className="list-group-item">An item</li>
                            <li className="list-group-item">A second item</li>
                            <li className="list-group-item">A third item</li>
                            <li className="list-group-item">A fourth item</li>
                            <li className="list-group-item">And a fifth one</li>
                        </ul>
                    </Example>

                    <Anchor tag="h2">
                        Active Items
                    </Anchor>

                    <p>
                        Add <code>.active</code> to a <code>.list-group-item</code> to indicate the current active
                        selection.
                    </p>

                    <Example>
                        <ul className="list-group">
                            <li className="list-group-item active" aria-current="true">An active item</li>
                            <li className="list-group-item">A second item</li>
                            <li className="list-group-item">A third item</li>
                            <li className="list-group-item">A fourth item</li>
                            <li className="list-group-item">And a fifth one</li>
                        </ul>
                    </Example>

                    <Anchor tag="h2">
                        Disabled Items
                    </Anchor>

                    <p>
                        Add <code>.disabled</code> to a <code>.list-group-item</code> to make
                        it <em>appear</em> disabled. Note that some elements with <code>.disabled</code> will also
                        require custom JavaScript to fully disable their click events (e.g., links).
                    </p>

                    <Example>
                        <ul className="list-group">
                            <li className="list-group-item disabled" aria-disabled="true">A disabled item</li>
                            <li className="list-group-item">A second item</li>
                            <li className="list-group-item">A third item</li>
                            <li className="list-group-item">A fourth item</li>
                            <li className="list-group-item">And a fifth one</li>
                        </ul>
                    </Example>

                    <Anchor tag="h2">
                        Links And Buttons
                    </Anchor>

                    <p>
                        Use <code>&lt;a&gt;</code>s or <code>&lt;button&gt;</code>s to create <em>actionable</em> list
                        group items with hover, disabled, and active states by
                        adding <code>.list-group-item-action</code>. We separate these pseudo-classes to ensure list
                        groups made of non-interactive elements (like <code>&lt;li&gt;</code>s
                        or <code>&lt;div&gt;</code>s) don't provide a click or tap affordance.
                    </p>

                    <Example>
                        <div className="list-group">
                            <a href="#" className="list-group-item list-group-item-action active" aria-current="true">
                                The current link item
                            </a>
                            <a href="#" className="list-group-item list-group-item-action">A second link item</a>
                            <a href="#" className="list-group-item list-group-item-action">A third link item</a>
                            <a href="#" className="list-group-item list-group-item-action">A fourth link item</a>
                            <a
                                className="list-group-item list-group-item-action disabled"
                                tabIndex={-1}
                                aria-disabled="true"
                            >
                                A disabled link item
                            </a>
                        </div>
                    </Example>

                    <p>
                        With <code>&lt;button&gt;</code>s, you can also make use of the <code>disabled</code> attribute
                        instead of the <code>.disabled</code> class. Sadly, <code>&lt;a&gt;</code>s don't support the
                        disabled attribute.
                    </p>

                    <Example>
                        <div className="list-group">
                            <button
                                type="button"
                                className="list-group-item list-group-item-action active"
                                aria-current="true"
                            >
                                The current button
                            </button>
                            <button type="button" className="list-group-item list-group-item-action">
                                A second item
                            </button>
                            <button type="button" className="list-group-item list-group-item-action">
                                A third button item
                            </button>
                            <button type="button" className="list-group-item list-group-item-action">
                                A fourth button item
                            </button>
                            <button type="button" className="list-group-item list-group-item-action" disabled>
                                A disabled button item
                            </button>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Flush
                    </Anchor>

                    <p>
                        Add <code>.list-group-flush</code> to remove some borders and rounded corners to render list
                        group items edge-to-edge in a parent container (e.g., cards).
                    </p>

                    <Example>
                        <ul className="list-group list-group-flush">
                            <li className="list-group-item">An item</li>
                            <li className="list-group-item">A second item</li>
                            <li className="list-group-item">A third item</li>
                            <li className="list-group-item">A fourth item</li>
                            <li className="list-group-item">And a fifth one</li>
                        </ul>
                    </Example>

                    <Anchor tag="h2">
                        With Badges
                    </Anchor>

                    <p>
                        Add badges to any list group item to show unread counts, activity, and more with the help of
                        some <a href="https://getbootstrap.com/docs/5.0/utilities/flex/">utilities</a>.
                    </p>

                    <Example>
                        <ul className="list-group">
                            <li className="list-group-item d-flex justify-content-between align-items-center">
                                A list item
                                <span className="badge badge-sa-primary badge-sa-pill">14</span>
                            </li>
                            <li className="list-group-item d-flex justify-content-between align-items-center">
                                A second list item
                                <span className="badge badge-sa-primary badge-sa-pill">2</span>
                            </li>
                            <li className="list-group-item d-flex justify-content-between align-items-center">
                                A third list item
                                <span className="badge badge-sa-primary badge-sa-pill">1</span>
                            </li>
                        </ul>
                    </Example>

                    <Anchor tag="h2">
                        Custom Content
                    </Anchor>

                    <p>
                        Add nearly any HTML within, even for linked list groups like the one below, with the help of <a
                        href="https://getbootstrap.com/docs/5.0/utilities/flex/">flexbox utilities</a>.
                    </p>

                    <Example>
                        <div className="list-group">
                            <a href="#" className="list-group-item list-group-item-action py-4 active" aria-current="true">
                                <div className="d-flex w-100 justify-content-between">
                                    <h5 className="mb-2">List group item heading</h5>
                                    <small>3 days ago</small>
                                </div>
                                <p className="mb-2">Some placeholder content in a paragraph.</p>
                                <small>And some small print.</small>
                            </a>
                            <a href="#" className="list-group-item list-group-item-action py-4">
                                <div className="d-flex w-100 justify-content-between">
                                    <h5 className="mb-2">List group item heading</h5>
                                    <small className="text-muted">3 days ago</small>
                                </div>
                                <p className="mb-2">Some placeholder content in a paragraph.</p>
                                <small className="text-muted">And some muted small print.</small>
                            </a>
                            <a href="#" className="list-group-item list-group-item-action py-4">
                                <div className="d-flex w-100 justify-content-between">
                                    <h5 className="mb-2">List group item heading</h5>
                                    <small className="text-muted">3 days ago</small>
                                </div>
                                <p className="mb-2">Some placeholder content in a paragraph.</p>
                                <small className="text-muted">And some muted small print.</small>
                            </a>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Checkboxes And Radios
                    </Anchor>

                    <p>
                        Place Bootstrap's checkboxes and radios within list group items and customize as needed.
                    </p>

                    <Example>
                        <div className="list-group">
                            <label className="list-group-item">
                                <input className="form-check-input me-4" type="checkbox" defaultValue="" />
                                First checkbox
                            </label>
                            <label className="list-group-item">
                                <input className="form-check-input me-4" type="checkbox" defaultValue="" />
                                Second checkbox
                            </label>
                            <label className="list-group-item">
                                <input className="form-check-input me-4" type="checkbox" defaultValue="" />
                                Third checkbox
                            </label>
                            <label className="list-group-item">
                                <input className="form-check-input me-4" type="checkbox" defaultValue="" />
                                Fourth checkbox
                            </label>
                            <label className="list-group-item">
                                <input className="form-check-input me-4" type="checkbox" defaultValue="" />
                                Fifth checkbox
                            </label>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
