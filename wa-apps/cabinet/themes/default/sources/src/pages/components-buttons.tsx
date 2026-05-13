import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import { url } from "../components/utils";

export default function() {
    const styles = [
        {key: 'primary', title: 'Primary'},
        {key: 'secondary', title: 'Secondary'},
        {key: 'success', title: 'Success'},
        {key: 'danger', title: 'Danger'},
        {key: 'warning', title: 'Warning'},
        {key: 'info', title: 'Info'},
        {key: 'light', title: 'Light'},
        {key: 'dark', title: 'Dark'},
    ];

    return (
        <Layout>
            <App>
                <Article
                    title="Buttons"
                    subtitle="Use Bootstrap's custom button styles for actions in forms, dialogs, and more with support for multiple sizes, states, and more."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Buttons'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Bootstrap includes several predefined button styles, each serving its own semantic purpose, with
                        a few extras thrown in for more control.
                    </p>

                    <Example>
                        <div className="row g-3">
                            {styles.map((style, styleIdx) => (
                                <div key={styleIdx} className="col-auto">
                                    <button type="button" className={`btn btn-${style.key}`}>
                                        Button
                                    </button>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Outline buttons
                    </Anchor>

                    <p>
                        In need of a button, but not the hefty background colors they bring? Replace the default
                        modifier classes with the <code>.btn-outline-*</code> ones to remove all background images and
                        colors on any button.
                    </p>

                    <Example>
                        <div className="row g-3">
                            {styles.map((style, styleIdx) => (
                                <div key={styleIdx} className="col-auto">
                                    <button type="button" className={`btn btn-outline-${style.key}`}>
                                        Button
                                    </button>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <div className="alert alert-info">
                        Some of the button styles use a relatively light foreground color, and should only be used on a
                        dark background in order to have sufficient contrast.
                    </div>

                    <Anchor tag="h2">
                        Sizes
                    </Anchor>

                    <p>
                        Fancy larger or smaller buttons? Add <code>.btn-lg</code> or <code>.btn-sm</code> for additional
                        sizes.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary btn-lg">Button</button>
                            </div>
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary">Button</button>
                            </div>
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary btn-sm">Button</button>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Multiline
                    </Anchor>

                    <p>
                        Buttons can also contain multi-line content.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary btn-lg">
                                    Button<br />Test multiline button
                                </button>
                            </div>
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary">
                                    Button<br />Test multiline button
                                </button>
                            </div>
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary btn-sm">
                                    Button<br />Test multiline button
                                </button>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Disabled State
                    </Anchor>

                    <p>
                        Make buttons look inactive by adding the <code>disabled</code> boolean attribute to
                        any <code>&lt;button&gt;</code> element. Disabled buttons have <code>pointer-events:
                        none</code> applied to, preventing hover and active states from triggering.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary btn-lg" disabled>Button</button>
                            </div>
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary" disabled>Button</button>
                            </div>
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary btn-sm" disabled>Button</button>
                            </div>
                        </div>
                    </Example>

                    <p>
                        Disabled buttons in the different color variations.
                    </p>

                    <Example>
                        <div className="row g-3">
                            {styles.map((style, styleIdx) => (
                                <div key={styleIdx} className="col-auto">
                                    <button type="button" className={`btn btn-${style.key}`} disabled>Button</button>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Loading State
                    </Anchor>

                    <p>
                        Turn buttons to the loading state by adding <code>.btn-sa-loading</code> class.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary btn-sa-loading btn-lg">Button</button>
                            </div>
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary btn-sa-loading">Button</button>
                            </div>
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary btn-sa-loading btn-sm">Button</button>
                            </div>
                        </div>
                    </Example>

                    <p>
                        Buttons in loading state and in different color variations.
                    </p>

                    <Example>
                        <div className="row g-3">
                            {styles.map((style, styleIdx) => (
                                <div key={styleIdx} className="col-auto">
                                    <button type="button" className={`btn btn-${style.key} btn-sa-loading`}>Button</button>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Pill
                    </Anchor>

                    <p>
                        Add the <code>.btn-sa-pill</code> class to get the rounded buttons.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary btn-sa-pill btn-lg">Button</button>
                            </div>
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary btn-sa-pill">Button</button>
                            </div>
                            <div className="col-auto">
                                <button type="button" className="btn btn-primary btn-sa-pill btn-sm">Button</button>
                            </div>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
