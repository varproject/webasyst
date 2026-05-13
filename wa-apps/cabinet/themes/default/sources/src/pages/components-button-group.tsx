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
                    title="Button Group"
                    subtitle="Group a series of buttons together on a single line or stack them in a vertical column."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Button Group'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Wrap a series of buttons with <code>.btn</code> in <code>.btn-group</code>.
                    </p>

                    <Example>
                        <div className="btn-group" role="group" aria-label="Basic example">
                            <button type="button" className="btn btn-primary">Left</button>
                            <button type="button" className="btn btn-primary">Middle</button>
                            <button type="button" className="btn btn-primary">Right</button>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Button toolbar
                    </Anchor>

                    <p>
                        Combine sets of button groups into button toolbars for more complex components. Use utility
                        classes as needed to space out groups, buttons, and more.
                    </p>

                    <Example>
                        <div className="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
                            <div className="btn-group me-3" role="group" aria-label="First group">
                                <button type="button" className="btn btn-primary">1</button>
                                <button type="button" className="btn btn-primary">2</button>
                                <button type="button" className="btn btn-primary">3</button>
                                <button type="button" className="btn btn-primary">4</button>
                            </div>
                            <div className="btn-group me-3" role="group" aria-label="Second group">
                                <button type="button" className="btn btn-secondary">5</button>
                                <button type="button" className="btn btn-secondary">6</button>
                                <button type="button" className="btn btn-secondary">7</button>
                            </div>
                            <div className="btn-group" role="group" aria-label="Third group">
                                <button type="button" className="btn btn-info">8</button>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Sizing
                    </Anchor>

                    <p>
                        Instead of applying button sizing classes to every button in a group, just
                        add <code>.btn-group-*</code> to each <code>.btn-group</code>, including each one when nesting
                        multiple groups.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col-auto">
                                <div className="btn-group btn-group-lg" role="group" aria-label="Large group">
                                    <button type="button" className="btn btn-primary">1</button>
                                    <button type="button" className="btn btn-primary">2</button>
                                    <button type="button" className="btn btn-primary">3</button>
                                </div>
                            </div>
                            <div className="col-auto">
                                <div className="btn-group" role="group" aria-label="Normal group">
                                    <button type="button" className="btn btn-primary">1</button>
                                    <button type="button" className="btn btn-primary">2</button>
                                    <button type="button" className="btn btn-primary">3</button>
                                </div>
                            </div>
                            <div className="col-auto">
                                <div className="btn-group btn-group-sm" role="group" aria-label="Small group">
                                    <button type="button" className="btn btn-primary">1</button>
                                    <button type="button" className="btn btn-primary">2</button>
                                    <button type="button" className="btn btn-primary">3</button>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Nesting
                    </Anchor>

                    <p>
                        Place a <code>.btn-group</code> within another <code>.btn-group</code> when you want dropdown
                        menus mixed with a series of buttons.
                    </p>

                    <Example>
                        <div className="btn-group" role="group" aria-label="Button group with nested dropdown">
                            <button type="button" className="btn btn-primary">1</button>
                            <button type="button" className="btn btn-primary">2</button>

                            <div className="btn-group" role="group">
                                <button id="btnGroupDrop1" type="button" className="btn btn-primary dropdown-toggle"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    Dropdown
                                </button>
                                <ul className="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                    <li><a className="dropdown-item" href="#">Dropdown link</a></li>
                                    <li><a className="dropdown-item" href="#">Dropdown link</a></li>
                                </ul>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Vertical Variation
                    </Anchor>

                    <p>
                        Make a set of buttons appear vertically stacked rather than horizontally. <strong>Split button
                        dropdowns are not supported here.</strong>
                    </p>

                    <Example>
                        <div className="btn-group-vertical" role="group" aria-label="Vertical button group">
                            <button type="button" className="btn btn-primary">1</button>
                            <button type="button" className="btn btn-primary">2</button>
                            <button type="button" className="btn btn-primary">3</button>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
