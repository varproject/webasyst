import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import classNames from 'classNames';
import { useSvg } from '@scompiler/0003-product/.scompiler/hooks';
import { url } from "../components/utils";

export default function() {
    const svg = useSvg();

    const styles = [
        'success',
        'danger',
        'warning',
        'info',
        'primary',
        'secondary',
        'dark',
        'light',
    ];

    return (
        <Layout>
            <App>
                <Article
                    title="Alerts"
                    subtitle="Provide contextual feedback messages for typical user actions with the handful of available and flexible alert messages."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Alerts'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        A basic alert is usually just a highlighted area containing an informational message. Scroll
                        down the page to learn about other alert options.
                    </p>

                    <Example>
                        <div className="alert alert-info mb-0" role="alert">
                            Hi, I'm a simple alert example. Scroll down to see what I can.
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Colors
                    </Anchor>

                    <p>
                        There are a total of eight color options available. Choose a color that matches the context of
                        the alert.
                    </p>

                    <Example>
                        {styles.map((style, styleIdx) => (
                            <div
                                key={styleIdx}
                                className={
                                    classNames(
                                        'alert',
                                        `alert-${style}`,
                                        {
                                            'mb-0': styleIdx === styles.length - 1,
                                        },
                                    )
                                }
                                role="alert"
                            >
                                A simple {style} alert — check it out!
                            </div>
                        ))}
                    </Example>

                    <Anchor tag="h2">
                        Link Color
                    </Anchor>

                    <p>
                        Use the <code>.alert-link</code> utility class to quickly provide matching colored links within
                        any alert.
                    </p>

                    <Example>
                        {styles.map((style, styleIdx) => (
                            <div
                                key={styleIdx}
                                className={
                                    classNames(
                                        'alert',
                                        `alert-${style}`,
                                        {
                                            'mb-0': styleIdx === styles.length - 1,
                                        },
                                    )
                                }
                                role="alert"
                            >
                                A simple {style} alert with <a href="#" className="alert-link">an example link</a>. Give
                                it a click if you like.
                            </div>
                        ))}
                    </Example>

                    <Anchor tag="h2">
                        Icons
                    </Anchor>

                    <p>
                        Use the <code>.alert-sa-has-icon</code>, <code>.alert-sa-icon</code>,
                        and <code>.alert-sa-content</code> classes to add an icon to the alert.
                    </p>

                    <Example>
                        <div className="alert alert-primary mb-0 alert-sa-has-icon" role="alert">
                            <div className="alert-sa-icon">
                                {svg('feather/info')}
                            </div>
                            <div className="alert-sa-content">
                                A simple alert with icon.
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Advanced Content
                    </Anchor>

                    <p>
                        Alerts can contain not only just text, but also a composition of headings, paragraphs, etc.
                    </p>

                    <Example>
                        <div className="alert alert-success mb-0" role="alert">
                            <h4 className="alert-heading">Well done!</h4>
                            <p>
                                Aww yeah, you successfully read this important alert message. This
                                example text is going to run a bit longer so that you can see how
                                spacing within an alert works with this kind of content.
                            </p>
                            <hr/>
                            <p className="mb-0">
                                Whenever you need to, be sure to use margin utilities to keep things
                                nice and tidy.
                            </p>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Dismissing
                    </Anchor>

                    <p>
                        You can let the user dismiss the alert, see the example below:
                    </p>

                    <Example>
                        <div className="alert alert-warning mb-0 alert-dismissible fade show" role="alert">
                            <strong>Holy guacamole!</strong> You should check in on some of those fields
                            below.{' '}

                            <button
                                type="button"
                                className="sa-close"
                                data-bs-dismiss="alert"
                                aria-label="Close"
                            />
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Card Style
                    </Anchor>

                    <p>
                        You can also use an alternate card-like alert style. Just change the style class to
                        the <code>.alert-sa-*-card</code> format.
                    </p>

                    <Example>
                        {styles.map((style, styleIdx) => (
                            <div
                                key={styleIdx}
                                className={
                                    classNames(
                                        'alert',
                                        `alert-sa-${style}-card`,
                                        'alert-sa-has-icon',
                                        'alert-dismissible',
                                        'fade',
                                        'show',
                                        {
                                            'mb-0': styleIdx === styles.length - 1,
                                        },
                                    )
                                }
                                role="alert"
                            >
                                <div className="alert-sa-icon">
                                    {svg('feather/info')}
                                </div>
                                <div className="alert-sa-content">
                                    A simple {style} alert with <a href="#" className="alert-link">an example link</a>. Give
                                    it a click if you like.
                                </div>

                                <button
                                    type="button"
                                    className="sa-close"
                                    data-bs-dismiss="alert"
                                    aria-label="Close"
                                />
                            </div>
                        ))}
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
