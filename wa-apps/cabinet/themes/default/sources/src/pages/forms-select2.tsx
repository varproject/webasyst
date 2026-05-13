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
                    title="Select2"
                    subtitle="Select2 gives you a customizable select box with support for searching, tagging, remote data sets, infinite scrolling, and many other options."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Forms'},
                        {title: 'Select2'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        Use the <code>.sa-select2</code> class on a regular <code>{'<select>'}</code> element to turn it
                        into an enhanced select2.
                    </p>

                    <Example>
                        <select className="sa-select2 form-select">
                            <optgroup label="Alaskan/Hawaiian Time Zone">
                                <option value="AK">Alaska</option>
                                <option value="HI">Hawaii</option>
                            </optgroup>
                            <optgroup label="Pacific Time Zone">
                                <option value="CA">California</option>
                                <option value="NV">Nevada</option>
                                <option value="OR">Oregon</option>
                                <option value="WA">Washington</option>
                            </optgroup>
                            <optgroup label="Mountain Time Zone">
                                <option value="AZ">Arizona</option>
                                <option value="CO">Colorado</option>
                                <option value="ID">Idaho</option>
                                <option value="MT">Montana</option>
                                <option value="NE">Nebraska</option>
                                <option value="NM">New Mexico</option>
                                <option value="ND">North Dakota</option>
                                <option value="UT">Utah</option>
                                <option value="WY">Wyoming</option>
                            </optgroup>
                            <optgroup label="Central Time Zone">
                                <option value="AL">Alabama</option>
                                <option value="AR">Arkansas</option>
                                <option value="IL">Illinois</option>
                                <option value="IA">Iowa</option>
                                <option value="KS">Kansas</option>
                                <option value="KY">Kentucky</option>
                                <option value="LA">Louisiana</option>
                                <option value="MN">Minnesota</option>
                                <option value="MS">Mississippi</option>
                                <option value="MO">Missouri</option>
                                <option value="OK">Oklahoma</option>
                                <option value="SD">South Dakota</option>
                                <option value="TX">Texas</option>
                                <option value="TN">Tennessee</option>
                                <option value="WI">Wisconsin</option>
                            </optgroup>
                            <optgroup label="Eastern Time Zone">
                                <option value="CT">Connecticut</option>
                                <option value="DE">Delaware</option>
                                <option value="FL">Florida</option>
                                <option value="GA">Georgia</option>
                                <option value="IN">Indiana</option>
                                <option value="ME">Maine</option>
                                <option value="MD">Maryland</option>
                                <option value="MA">Massachusetts</option>
                                <option value="MI">Michigan</option>
                                <option value="NH">New Hampshire</option>
                                <option value="NJ">New Jersey</option>
                                <option value="NY">New York</option>
                                <option value="NC">North Carolina</option>
                                <option value="OH">Ohio</option>
                                <option value="PA">Pennsylvania</option>
                                <option value="RI">Rhode Island</option>
                                <option value="SC">South Carolina</option>
                                <option value="VT">Vermont</option>
                                <option value="VA">Virginia</option>
                                <option value="WV">West Virginia</option>
                            </optgroup>
                        </select>
                    </Example>

                    <p>
                        Select2 also supports multi-value select boxes. The select below is declared with
                        the <code>multiple</code> attribute.
                    </p>

                    <Example>
                        <select className="sa-select2 form-select" multiple>
                            <optgroup label="Alaskan/Hawaiian Time Zone">
                                <option value="AK" selected>Alaska</option>
                                <option value="HI">Hawaii</option>
                            </optgroup>
                            <optgroup label="Pacific Time Zone">
                                <option value="CA">California</option>
                                <option value="NV">Nevada</option>
                                <option value="OR">Oregon</option>
                                <option value="WA">Washington</option>
                            </optgroup>
                            <optgroup label="Mountain Time Zone">
                                <option value="AZ">Arizona</option>
                                <option value="CO">Colorado</option>
                                <option value="ID">Idaho</option>
                                <option value="MT">Montana</option>
                                <option value="NE">Nebraska</option>
                                <option value="NM">New Mexico</option>
                                <option value="ND">North Dakota</option>
                                <option value="UT">Utah</option>
                                <option value="WY">Wyoming</option>
                            </optgroup>
                            <optgroup label="Central Time Zone">
                                <option value="AL">Alabama</option>
                                <option value="AR">Arkansas</option>
                                <option value="IL">Illinois</option>
                                <option value="IA">Iowa</option>
                                <option value="KS">Kansas</option>
                                <option value="KY">Kentucky</option>
                                <option value="LA">Louisiana</option>
                                <option value="MN">Minnesota</option>
                                <option value="MS">Mississippi</option>
                                <option value="MO">Missouri</option>
                                <option value="OK">Oklahoma</option>
                                <option value="SD">South Dakota</option>
                                <option value="TX">Texas</option>
                                <option value="TN">Tennessee</option>
                                <option value="WI">Wisconsin</option>
                            </optgroup>
                            <optgroup label="Eastern Time Zone">
                                <option value="CT">Connecticut</option>
                                <option value="DE">Delaware</option>
                                <option value="FL">Florida</option>
                                <option value="GA">Georgia</option>
                                <option value="IN">Indiana</option>
                                <option value="ME">Maine</option>
                                <option value="MD">Maryland</option>
                                <option value="MA">Massachusetts</option>
                                <option value="MI">Michigan</option>
                                <option value="NH">New Hampshire</option>
                                <option value="NJ">New Jersey</option>
                                <option value="NY">New York</option>
                                <option value="NC">North Carolina</option>
                                <option value="OH">Ohio</option>
                                <option value="PA">Pennsylvania</option>
                                <option value="RI">Rhode Island</option>
                                <option value="SC">South Carolina</option>
                                <option value="VT">Vermont</option>
                                <option value="VA">Virginia</option>
                                <option value="WV">West Virginia</option>
                            </optgroup>
                        </select>
                    </Example>

                    <Anchor tag="h2">
                        Single-select
                    </Anchor>

                    <Anchor tag="h3" idPrefix="single-select-">
                        Sizing
                    </Anchor>

                    <p>
                        Set heights using classes like <code>.form-select-lg</code> and <code>.form-select-sm</code>.
                    </p>

                    <Example>
                        <div>
                            <select className="sa-select2 form-select form-select-lg">
                                <option selected>Large</option>
                            </select>
                        </div>
                        <div className="mt-3">
                            <select className="sa-select2 form-select">
                                <option selected>Normal</option>
                            </select>
                        </div>
                        <div className="mt-3">
                            <select className="sa-select2 form-select form-select-sm">
                                <option selected>Small</option>
                            </select>
                        </div>
                    </Example>

                    <Anchor tag="h3" idPrefix="single-select-">
                        States
                    </Anchor>

                    <p>A select can be in several different states. Below you can see a demo of these states:</p>

                    <Example>
                        <div>
                            <select className="sa-select2 form-select">
                                <option selected>Normal</option>
                            </select>
                        </div>
                        <div className="mt-3">
                            <select className="sa-select2 form-select" disabled>
                                <option selected>Disabled</option>
                            </select>
                        </div>
                        <div className="mt-3">
                            <select className="sa-select2 form-select is-valid">
                                <option selected>Valid</option>
                            </select>
                        </div>
                        <div className="mt-3">
                            <select className="sa-select2 form-select is-invalid">
                                <option selected>Invalid</option>
                            </select>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Multi-select
                    </Anchor>

                    <Anchor tag="h3" idPrefix="multi-select-">
                        Sizing
                    </Anchor>

                    <p>
                        Set heights using classes like <code>.form-select-lg</code> and <code>.form-select-sm</code>.
                    </p>

                    <Example>
                        <div>
                            <select multiple className="sa-select2 form-select form-select-lg">
                                <option selected>Large</option>
                                <option selected>One</option>
                                <option>Two</option>
                                <option>Three</option>
                            </select>
                        </div>
                        <div className="mt-3">
                            <select multiple className="sa-select2 form-select">
                                <option selected>Normal</option>
                                <option selected>One</option>
                                <option>Two</option>
                                <option>Three</option>
                            </select>
                        </div>
                        <div className="mt-3">
                            <select multiple className="sa-select2 form-select form-select-sm">
                                <option selected>Small</option>
                                <option selected>One</option>
                                <option>Two</option>
                                <option>Three</option>
                            </select>
                        </div>
                    </Example>

                    <Anchor tag="h3" idPrefix="multi-select-">
                        States
                    </Anchor>

                    <p>A select can be in several different states. Below you can see a demo of these states:</p>

                    <Example>
                        <div>
                            <select multiple className="sa-select2 form-select">
                                <option selected>Normal</option>
                            </select>
                        </div>
                        <div className="mt-3">
                            <select multiple className="sa-select2 form-select" disabled>
                                <option selected>Disabled</option>
                            </select>
                        </div>
                        <div className="mt-3">
                            <select multiple className="sa-select2 form-select is-valid">
                                <option selected>Valid</option>
                            </select>
                        </div>
                        <div className="mt-3">
                            <select multiple className="sa-select2 form-select is-invalid">
                                <option selected>Invalid</option>
                            </select>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
