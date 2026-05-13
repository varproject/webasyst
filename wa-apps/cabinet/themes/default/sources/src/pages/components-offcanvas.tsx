import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    const directions = ['Top', 'End', 'Bottom', 'Start'];

    return (
        <Layout>
            <App>
                <Article
                    title="Offcanvas"
                    subtitle="Build hidden sidebars into your project for navigation, shopping carts, and more with a few classes and our JavaScript plugin."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Offcanvas'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Use the buttons below to show and hide an offcanvas element via JavaScript that toggles the
                        <code>.show</code> class on an element with the <code>.offcanvas</code> class.
                    </p>

                    <ul>
                        <li><code>.offcanvas</code> hides content (default)</li>
                        <li><code>.offcanvas.show</code> shows content</li>
                    </ul>

                    <p>
                        You can use a link with the <code>href</code> attribute, or a button with the
                        <code>data-bs-target</code> attribute. In both cases, the
                        <code>data-bs-toggle="offcanvas"</code> is required.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <a
                                    className="btn btn-primary"
                                    data-bs-toggle="offcanvas"
                                    href="#offcanvasExample"
                                    role="button"
                                    aria-controls="offcanvasExample"
                                >
                                    Link with href
                                </a>
                            </div>
                            <div className="col-auto">
                                <button
                                    className="btn btn-primary"
                                    type="button"
                                    data-bs-toggle="offcanvas"
                                    data-bs-target="#offcanvasExample"
                                    aria-controls="offcanvasExample"
                                >
                                    Button with data-bs-target
                                </button>
                            </div>
                        </div>

                        <div
                            className="offcanvas offcanvas-start"
                            tabIndex={-1}
                            id="offcanvasExample"
                            aria-labelledby="offcanvasExampleLabel"
                        >
                            <div className="offcanvas-header">
                                <h5 className="offcanvas-title" id="offcanvasExampleLabel">Offcanvas</h5>
                                <button
                                    type="button"
                                    className="sa-close sa-close--modal"
                                    data-bs-dismiss="offcanvas"
                                    aria-label="Close"
                                />
                            </div>
                            <div className="offcanvas-body">
                                <div>
                                    Some text as placeholder. In real life you can have the elements you have chosen.
                                    Like, text, images, lists, etc.
                                </div>
                                <div className="dropdown mt-4">
                                    <button
                                        className="btn btn-secondary dropdown-toggle"
                                        type="button"
                                        id="dropdown-menu-button-inside-offcanvas"
                                        data-bs-toggle="dropdown"
                                    >
                                        Dropdown button
                                    </button>
                                    <ul className="dropdown-menu" aria-labelledby="dropdown-menu-button-inside-offcanvas">
                                        <li><a className="dropdown-item" href="#">Action</a></li>
                                        <li><a className="dropdown-item" href="#">Another action</a></li>
                                        <li><a className="dropdown-item" href="#">Something else here</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Placement
                    </Anchor>

                    <p>
                        There's no default placement for offcanvas components, so you must add one of the modifier
                        classes below;
                    </p>

                    <ul>
                        <li>
                            <code>.offcanvas-start</code> places offcanvas on the left of the viewport (shown above)
                        </li>
                        <li><code>.offcanvas-end</code> places offcanvas on the right of the viewport</li>
                        <li><code>.offcanvas-top</code> places offcanvas on the top of the viewport</li>
                        <li><code>.offcanvas-bottom</code> places offcanvas on the bottom of the viewport</li>
                    </ul>

                    <p>Try the top, right, and bottom examples out below.</p>

                    <Example>
                        <div className="row g-3">
                            {directions.map(direction => (
                                <div key={direction} className="col-auto">
                                    <button
                                        className="btn btn-primary"
                                        type="button"
                                        data-bs-toggle="offcanvas"
                                        data-bs-target={`#offcanvas${direction}`}
                                        aria-controls={`offcanvas${direction}`}
                                    >
                                        {direction} offcanvas
                                    </button>
                                </div>
                            ))}
                        </div>

                        {directions.map(direction => (
                            <div
                                key={direction}
                                className={`offcanvas offcanvas-${direction.toLowerCase()}`}
                                tabIndex={-1}
                                id={`offcanvas${direction}`}
                                aria-labelledby={`offcanvas${direction}Label`}
                            >
                                <div className="offcanvas-header">
                                    <h5 id={`offcanvas${direction}Label`}>Offcanvas {direction.toLowerCase()}</h5>
                                    <button
                                        type="button"
                                        className="sa-close sa-close--modal"
                                        data-bs-dismiss="offcanvas"
                                        aria-label="Close"
                                    />
                                </div>
                                <div className="offcanvas-body">
                                    ...
                                </div>
                            </div>
                        ))}
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
