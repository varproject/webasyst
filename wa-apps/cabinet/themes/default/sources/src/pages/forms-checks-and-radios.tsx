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
                    title="Checks And Radios"
                    subtitle="Consistent cross-browser and cross-device checkboxes and radios. Documentation and description for the corresponding controls."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Forms'},
                        {title: 'Checks And Radios'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <Example>
                        <div className="row">
                            <div className="col">
                                <label className="form-check">
                                    <input type="checkbox" className="form-check-input" />
                                    <span className="form-check-label">Checkbox one</span>
                                </label>
                                <label className="form-check">
                                    <input type="checkbox" className="form-check-input" />
                                    <span className="form-check-label">Checkbox two</span>
                                </label>
                                <label className="form-check">
                                    <input type="checkbox" className="form-check-input" />
                                    <span className="form-check-label">Checkbox three</span>
                                </label>
                            </div>
                            <div className="col">
                                <label className="form-check">
                                    <input type="radio" className="form-check-input" name="exampleRadio1" />
                                    <span className="form-check-label">Radio one</span>
                                </label>
                                <label className="form-check">
                                    <input type="radio" className="form-check-input" name="exampleRadio1" />
                                    <span className="form-check-label">Radio two</span>
                                </label>
                                <label className="form-check">
                                    <input type="radio" className="form-check-input" name="exampleRadio1" />
                                    <span className="form-check-label">Radio three</span>
                                </label>
                            </div>
                            <div className="col">
                                <label className="form-check form-switch">
                                    <input type="checkbox" className="form-check-input" />
                                    <span className="form-check-label">Switch one</span>
                                </label>
                                <label className="form-check form-switch">
                                    <input type="checkbox" className="form-check-input" />
                                    <span className="form-check-label">Switch two</span>
                                </label>
                                <label className="form-check form-switch">
                                    <input type="checkbox" className="form-check-input" />
                                    <span className="form-check-label">Switch three</span>
                                </label>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Checkbox States
                    </Anchor>

                    <p>A checkbox can be in several different states. Below you can see a demo of these states:</p>

                    <Example>
                        <div className="row">
                            <div className="col">
                                <label className="form-check">
                                    <input type="checkbox" className="form-check-input" />
                                    <span className="form-check-label">Normal</span>
                                </label>
                                <label className="form-check">
                                    <input type="checkbox" className="form-check-input sa-indeterminate" />
                                    <span className="form-check-label">Indeterminate</span>
                                </label>
                                <label className="form-check">
                                    <input type="checkbox" className="form-check-input" defaultChecked />
                                    <span className="form-check-label">Checked</span>
                                </label>
                                <label className="form-check">
                                    <input type="checkbox" className="form-check-input is-valid" />
                                    <span className="form-check-label">Valid</span>
                                </label>
                            </div>
                            <div className="col">
                                <label className="form-check">
                                    <input type="checkbox" className="form-check-input" disabled />
                                    <span className="form-check-label">Normal Disabled</span>
                                </label>
                                <label className="form-check">
                                    <input type="checkbox" className="form-check-input sa-indeterminate" disabled />
                                    <span className="form-check-label">Indeterminate Disabled</span>
                                </label>
                                <label className="form-check">
                                    <input type="checkbox" className="form-check-input" defaultChecked disabled />
                                    <span className="form-check-label">Checked Disabled</span>
                                </label>
                                <label className="form-check">
                                    <input type="checkbox" className="form-check-input is-invalid" />
                                    <span className="form-check-label">Invalid</span>
                                </label>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Radio States
                    </Anchor>

                    <p>A radio can be in several different states. Below you can see a demo of these states:</p>

                    <Example>
                        <div className="row">
                            <div className="col">
                                <label className="form-check">
                                    <input type="radio" className="form-check-input" name="exampleRadio2" />
                                    <span className="form-check-label">Normal</span>
                                </label>
                                <label className="form-check">
                                    <input type="radio" className="form-check-input" name="exampleRadio2" defaultChecked />
                                    <span className="form-check-label">Checked</span>
                                </label>
                                <label className="form-check">
                                    <input type="radio" className="form-check-input is-valid" name="exampleRadio2" />
                                    <span className="form-check-label">Valid</span>
                                </label>
                            </div>
                            <div className="col">
                                <label className="form-check">
                                    <input type="radio" className="form-check-input" disabled />
                                    <span className="form-check-label">Normal Disabled</span>
                                </label>
                                <label className="form-check">
                                    <input type="radio" className="form-check-input" defaultChecked disabled />
                                    <span className="form-check-label">Checked Disabled</span>
                                </label>
                                <label className="form-check">
                                    <input type="radio" className="form-check-input is-invalid" name="exampleRadio2" />
                                    <span className="form-check-label">Invalid</span>
                                </label>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Switches States
                    </Anchor>

                    <p>A switches can be in several different states. Below you can see a demo of these states:</p>

                    <Example>
                        <div className="row">
                            <div className="col">
                                <label className="form-check form-switch">
                                    <input type="checkbox" className="form-check-input" />
                                    <span className="form-check-label">Normal</span>
                                </label>
                                <label className="form-check form-switch">
                                    <input type="checkbox" className="form-check-input" defaultChecked />
                                    <span className="form-check-label">Checked</span>
                                </label>
                                <label className="form-check form-switch">
                                    <input type="checkbox" className="form-check-input is-valid" />
                                    <span className="form-check-label">Valid</span>
                                </label>
                            </div>
                            <div className="col">
                                <label className="form-check form-switch">
                                    <input type="checkbox" className="form-check-input" disabled />
                                    <span className="form-check-label">Normal Disabled</span>
                                </label>
                                <label className="form-check form-switch">
                                    <input type="checkbox" className="form-check-input" defaultChecked disabled />
                                    <span className="form-check-label">Checked Disabled</span>
                                </label>
                                <label className="form-check form-switch">
                                    <input type="checkbox" className="form-check-input is-invalid" />
                                    <span className="form-check-label">Invalid</span>
                                </label>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Inline
                    </Anchor>

                    <p>
                        Group checkboxes or radios on the same horizontal row by
                        adding <code>.custom-control-inline</code> to
                        any <code>.custom-radio</code> or <code>.custom-checkbox</code> or <code>.custom-switch</code>.
                    </p>

                    <Example>
                        <div>
                            <label className="form-check form-check-inline">
                                <input type="checkbox" className="form-check-input" />
                                <span className="form-check-label">1</span>
                            </label>
                            <label className="form-check form-check-inline">
                                <input type="checkbox" className="form-check-input" />
                                <span className="form-check-label">2</span>
                            </label>
                            <label className="form-check form-check-inline">
                                <input type="checkbox" className="form-check-input" disabled />
                                <span className="form-check-label">3 (disabled)</span>
                            </label>
                        </div>
                        <div className="mt-2">
                            <label className="form-check form-check-inline">
                                <input type="radio" className="form-check-input" name="exampleRadio3" />
                                <span className="form-check-label">1</span>
                            </label>
                            <label className="form-check form-check-inline">
                                <input type="radio" className="form-check-input" name="exampleRadio3" />
                                <span className="form-check-label">2</span>
                            </label>
                            <label className="form-check form-check-inline">
                                <input type="radio" className="form-check-input" name="exampleRadio3" disabled />
                                <span className="form-check-label">3 (disabled)</span>
                            </label>
                        </div>
                        <div className="mt-2">
                            <label className="form-check form-switch form-check-inline">
                                <input type="checkbox" className="form-check-input" />
                                <span className="form-check-label">1</span>
                            </label>
                            <label className="form-check form-switch form-check-inline">
                                <input type="checkbox" className="form-check-input" />
                                <span className="form-check-label">2</span>
                            </label>
                            <label className="form-check form-switch form-check-inline">
                                <input type="checkbox" className="form-check-input" disabled />
                                <span className="form-check-label">3 (disabled)</span>
                            </label>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Without labels
                    </Anchor>

                    <p>
                        Omit the wrapping <code>.form-check</code> for checkboxes and radios that have no label text.
                        Remember to still provide some form of accessible name for assistive technologies (for instance,
                        using <code>aria-label</code>).
                    </p>

                    <Example>
                        <div>
                            <input type="checkbox" className="form-check-input" aria-label="..." />
                        </div>
                        <div className="mt-2">
                            <input type="radio" className="form-check-input" aria-label="..." />
                        </div>
                        <div className="mt-2">
                            <input type="checkbox" className="form-check-input form-switch-without-label" aria-label="..." />
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
