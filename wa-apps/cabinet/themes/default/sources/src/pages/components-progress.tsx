import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import classNames from "classNames";
import { url } from "../components/utils";

export default function() {
    return (
        <Layout>
            <App>
                <Article
                    title="Progress"
                    subtitle="Documentation and examples for using Bootstrap custom progress bars featuring support for stacked bars, animated backgrounds, and text labels."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Progress'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Progress components are built with two HTML elements, some CSS to set the width, and a few
                        attributes.
                    </p>

                    <ul>
                        <li>
                            We use the <code>.progress</code> as a wrapper to indicate the max value of the progress
                            bar.
                        </li>
                        <li>
                            We use the inner <code>.progress-bar</code> to indicate the progress so far.
                        </li>
                        <li>
                            The <code>.progress-bar</code> requires an inline style, utility class, or custom CSS to set
                            their width.
                        </li>
                        <li>
                            The <code>.progress-bar</code> also requires
                            some <code>role</code> and <code>aria</code> attributes to make it accessible.
                        </li>
                    </ul>

                    <p>Put that all together, and you have the following examples.</p>

                    <Example>
                        {[0, 25, 50, 75, 100].map((percent, idx) => (
                            <div key={percent} className={classNames('progress', {'mt-4': idx > 0})} style={{'--sa-progress--value': `${percent}%`} as any}>
                                <div
                                    className="progress-bar progress-bar-sa-primary"
                                    role="progressbar"
                                    aria-valuenow={percent}
                                    aria-valuemin={0}
                                    aria-valuemax={100}
                                />
                            </div>
                        ))}
                    </Example>

                    <Anchor tag="h2">
                        Labels
                    </Anchor>

                    <p>Add labels to your progress bars by placing text within the <code>.progress-bar</code>.</p>

                    <Example>
                        <div className="progress" style={{'--sa-progress--value': '25%'} as any}>
                            <div
                                className="progress-bar progress-bar-sa-primary"
                                role="progressbar"
                                aria-valuenow={25}
                                aria-valuemin={0}
                                aria-valuemax={100}
                            >
                                25%
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Height
                    </Anchor>

                    <p>
                        We only set a <code>height</code> value on the <code>.progress</code>, so if you change that
                        value the inner <code>.progress-bar</code> will automatically resize accordingly.
                    </p>

                    <Example>
                        <div className="progress" style={{height: '8px', '--sa-progress--value': '25%'} as any}>
                            <div
                                className="progress-bar progress-bar-sa-primary"
                                role="progressbar"
                                aria-valuenow={25}
                                aria-valuemin={0}
                                aria-valuemax={100}
                            />
                        </div>
                        <div className="progress mt-4" style={{height: '20px', '--sa-progress--value': '25%'} as any}>
                            <div
                                className="progress-bar progress-bar-sa-primary"
                                role="progressbar"
                                aria-valuenow={25}
                                aria-valuemin={0}
                                aria-valuemax={100}
                            />
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Backgrounds
                    </Anchor>

                    <p>There are several different color options for the appearance of progress bars.</p>

                    <Example>
                        {['primary', 'success', 'info', 'warning', 'danger', 'dark'].map((style, idx) => {
                            const percent = 100 * ((idx + 1) / 6);

                            return (
                                <div key={style} className={classNames('progress', {'mt-4': idx > 0})} style={{'--sa-progress--value': `${percent}%`} as any}>
                                    <div
                                        className={`progress-bar progress-bar-sa-${style}`}
                                        role="progressbar"
                                        aria-valuenow={percent}
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                    >
                                        {percent.toFixed(0)}%
                                    </div>
                                </div>
                            );
                        })}
                    </Example>

                    <Anchor tag="h2">
                        Striped
                    </Anchor>

                    <p>
                        Add <code>.progress-bar-striped</code> to any <code>.progress-bar</code> to apply a stripe via
                        CSS gradient over the progress bar's background color.
                    </p>

                    <Example>
                        {['primary', 'success', 'info', 'warning', 'danger', 'dark'].map((style, idx) => {
                            const percent = 100 * ((idx + 1) / 6);

                            return (
                                <div key={style} className={classNames('progress', {'mt-4': idx > 0})} style={{'--sa-progress--value': `${percent}%`} as any}>
                                    <div
                                        className={`progress-bar progress-bar-sa-${style} progress-bar-striped`}
                                        role="progressbar"
                                        aria-valuenow={percent}
                                        aria-valuemin={0}
                                        aria-valuemax={100}
                                    />
                                </div>
                            );
                        })}
                    </Example>

                    <Anchor tag="h2">
                        Animated stripes
                    </Anchor>

                    <p>
                        The striped gradient can also be animated.
                        Add <code>.progress-bar-animated</code> to <code>.progress-bar</code> to animate the stripes
                        right to left via CSS3 animations.
                    </p>

                    <Example>
                        <div className="progress" style={{'--sa-progress--value': '25%'} as any}>
                            <div
                                className="progress-bar progress-bar-sa-primary progress-bar-striped progress-bar-animated"
                                role="progressbar"
                                aria-valuenow={25}
                                aria-valuemin={0}
                                aria-valuemax={100}
                            />
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
