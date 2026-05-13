import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    const styles = [
        {key: 'sa-primary', title: 'Primary'},
        {key: 'sa-secondary', title: 'Secondary'},
        {key: 'sa-success', title: 'Success'},
        {key: 'sa-danger', title: 'Danger'},
        {key: 'sa-warning', title: 'Warning'},
        {key: 'sa-info', title: 'Info'},
        {key: 'sa-light', title: 'Light'},
        {key: 'sa-dark', title: 'Dark'},
    ];

    return (
        <Layout>
            <App>
                <Article
                    title="Placeholders"
                    subtitle="Use loading placeholders for your components or pages to indicate something may still be loading."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Placeholders'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        In the example below, we take a typical card component and recreate it with placeholders applied
                        to create a "loading card". Size and proportions are the same between the two.
                    </p>

                    <Example>
                        <div className="row">
                            <div className="col">
                                <div className="card">
                                    <svg
                                        className="bd-placeholder-img card-img-top"
                                        width="100%"
                                        height="180"
                                        xmlns="http://www.w3.org/2000/svg"
                                        role="img"
                                        aria-label="Placeholder"
                                        preserveAspectRatio="xMidYMid slice"
                                        focusable="false"
                                    >
                                        <title>Placeholder</title>
                                        <rect width="100%" height="100%" fill="#20c997" />
                                    </svg>

                                    <div className="card-body">
                                        <h5 className="card-title">Card title</h5>
                                        <p className="card-text">
                                            Some quick example text to build on the card title and make up the bulk of
                                            the card's content.
                                        </p>
                                        <a href="#" className="btn btn-primary">Go somewhere</a>
                                    </div>
                                </div>
                            </div>
                            <div className="col">
                                <div className="card w-20x" aria-hidden="true">
                                    <svg
                                        className="bd-placeholder-img card-img-top"
                                        width="100%"
                                        height="180"
                                        xmlns="http://www.w3.org/2000/svg"
                                        role="img"
                                        aria-label="Placeholder"
                                        preserveAspectRatio="xMidYMid slice"
                                        focusable="false"
                                    >
                                        <title>Placeholder</title>
                                        <rect width="100%" height="100%" fill="#868e96" />
                                    </svg>

                                    <div className="card-body">
                                        <div className="h5 card-title placeholder-glow">
                                            <span className="placeholder col-6" />
                                        </div>
                                        <p className="card-text placeholder-glow">
                                            <span className="placeholder col-7" />
                                            <span className="placeholder col-4" />
                                            <span className="placeholder col-4" />
                                            <span className="placeholder col-6" />
                                            <span className="placeholder col-8" />
                                        </p>
                                        <a
                                            href="#"
                                            tabIndex={-1}
                                            className="btn btn-primary disabled placeholder col-6"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>

                    {/*
                    // --------------------------------
                    */}

                    <Anchor tag="h2">
                        How it works
                    </Anchor>

                    <p>
                        Create placeholders with the <code>.placeholder</code> class and a grid column class
                        (e.g., <code>.col-6</code>) to set the <code>width</code>. They can replace the text inside an
                        element or as be added as a modifier class to an existing component.
                    </p>

                    <p>
                        We apply additional styling to <code>.btn</code>s via <code>::before</code> to ensure
                        the <code>height</code> is respected. You may extend this pattern for other situations as
                        needed, or add a <code>&amp;nbsp;</code> within the element to reflect the height when actual
                        text is rendered in its place.
                    </p>

                    <Example>
                        <p aria-hidden="true">
                            <span className="placeholder col-6" />
                        </p>

                        <a href="#" className="btn btn-primary disabled placeholder col-4" aria-hidden="true" />
                    </Example>

                    {/*
                    // --------------------------------
                    */}

                    <Anchor tag="h2">
                        Width
                    </Anchor>

                    <p>
                        You can change the <code>width</code> through grid column classes, width utilities, or inline
                        styles.
                    </p>

                    <Example>
                        <div>
                            <span className="placeholder col-6" />
                        </div>
                        <div>
                            <span className="placeholder w-75" />
                        </div>
                        <div>
                            <span className="placeholder" style={{width: '25%'}} />
                        </div>
                    </Example>

                    {/*
                    // --------------------------------
                    */}

                    <Anchor tag="h2">
                        Color
                    </Anchor>

                    <p>
                        By default, the <code>placeholder</code> uses <code>currentColor</code>. This can be overriden
                        with a custom color or utility class.
                    </p>

                    <Example>
                        <div><span className="placeholder col-12" /></div>

                        <div><span className="placeholder col-12 bg-primary" /></div>
                        <div><span className="placeholder col-12 bg-secondary" /></div>
                        <div><span className="placeholder col-12 bg-success" /></div>
                        <div><span className="placeholder col-12 bg-danger" /></div>
                        <div><span className="placeholder col-12 bg-warning" /></div>
                        <div><span className="placeholder col-12 bg-info" /></div>
                        <div><span className="placeholder col-12 bg-light" /></div>
                        <div><span className="placeholder col-12 bg-dark" /></div>
                    </Example>

                    {/*
                    // --------------------------------
                    */}

                    <Anchor tag="h2">
                        Sizing
                    </Anchor>

                    <p>
                        The size of <code>.placeholder</code>s are based on the typographic style of the parent element.
                        Customize them with sizing modifiers: <code>.placeholder-lg</code>, <code>.placeholder-sm</code>,
                        or <code>.placeholder-xs</code>.
                    </p>

                    <Example>
                        <div><span className="placeholder col-12 placeholder-lg" /></div>
                        <div><span className="placeholder col-12" /></div>
                        <div><span className="placeholder col-12 placeholder-sm" /></div>
                        <div><span className="placeholder col-12 placeholder-xs" /></div>
                    </Example>

                    {/*
                    // --------------------------------
                    */}

                    <Anchor tag="h2">
                        Animation
                    </Anchor>

                    <p>
                        Animate placeholders with <code>.placeholder-glow</code> or <code>.placeholder-wave</code> to
                        better convey the perception of something being <em>actively</em> loaded.
                    </p>

                    <Example>
                        <p className="placeholder-glow">
                            <span className="placeholder col-12" />
                        </p>

                        <p className="placeholder-wave">
                            <span className="placeholder col-12" />
                        </p>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
