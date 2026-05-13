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
                    title="Layout"
                    subtitle="Give your forms some structure – from inline to horizontal to custom grid implementations – with our form layout options."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Forms'},
                        {title: 'Layout'},
                    ]}
                >
                    <Anchor tag="h2">
                        Utilities
                    </Anchor>

                    <p>
                        Margin utilities are the easiest way to add some structure to forms. They provide basic grouping
                        of labels, controls, optional form text, and form validation messaging. We recommend sticking
                        to <code>margin-bottom</code> utilities, and using a single direction throughout the form for
                        consistency.
                    </p>

                    <p>
                        Feel free to build your forms however you like,
                        with <code>&lt;fieldset&gt;</code>s, <code>&lt;div&gt;</code>s, or nearly any other element.
                    </p>

                    <Example>
                        <div className="mb-4">
                            <label htmlFor="formGroupExampleInput" className="form-label">Example label</label>
                            <input
                                type="text"
                                className="form-control"
                                id="formGroupExampleInput"
                                placeholder="Example input placeholder"
                            />
                        </div>
                        <div>
                            <label htmlFor="formGroupExampleInput2" className="form-label">Another label</label>
                            <input
                                type="text"
                                className="form-control"
                                id="formGroupExampleInput2"
                                placeholder="Another input placeholder"
                            />
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Form Grid
                    </Anchor>

                    <p>
                        More complex forms can be built using our grid classes. Use these for form layouts that require
                        multiple columns, varied widths, and additional alignment options.
                    </p>

                    <Example>
                        <div className="row">
                            <div className="col">
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="First name"
                                    aria-label="First name"
                                />
                            </div>
                            <div className="col">
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Last name"
                                    aria-label="Last name"
                                />
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Gutters
                    </Anchor>

                    <p>
                        By adding gutter modifier classes, you can have control over the gutter width in as well the
                        inline as block direction.
                    </p>

                    <Example>
                        <div className="row g-3">
                            <div className="col">
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="First name"
                                    aria-label="First name"
                                />
                            </div>
                            <div className="col">
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Last name"
                                    aria-label="Last name"
                                />
                            </div>
                        </div>
                    </Example>

                    <p>More complex layouts can also be created with the grid system.</p>

                    <Example>
                        <form className="row g-4">
                            <div className="col-md-6">
                                <label htmlFor="inputEmail4" className="form-label">Email</label>
                                <input
                                    type="email"
                                    className="form-control"
                                    id="inputEmail4"
                                />
                            </div>
                            <div className="col-md-6">
                                <label htmlFor="inputPassword4" className="form-label">Password</label>
                                <input
                                    type="password"
                                    className="form-control"
                                    id="inputPassword4"
                                />
                            </div>
                            <div className="col-12">
                                <label htmlFor="inputAddress" className="form-label">Address</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="inputAddress"
                                    placeholder="1234 Main St"
                                />
                            </div>
                            <div className="col-12">
                                <label htmlFor="inputAddress2" className="form-label">Address 2</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="inputAddress2"
                                    placeholder="Apartment, studio, or floor"
                                />
                            </div>
                            <div className="col-md-6">
                                <label htmlFor="inputCity" className="form-label">City</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="inputCity"
                                />
                            </div>
                            <div className="col-md-4">
                                <label htmlFor="inputState" className="form-label">State</label>
                                <select id="inputState" className="form-select">
                                    <option selected>Choose...</option>
                                    <option>...</option>
                                </select>
                            </div>
                            <div className="col-md-2">
                                <label htmlFor="inputZip" className="form-label">Zip</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="inputZip"
                                />
                            </div>
                            <div className="col-12">
                                <div className="form-check">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="gridCheck"
                                    />
                                    <label className="form-check-label" htmlFor="gridCheck">
                                        Check me out
                                    </label>
                                </div>
                            </div>
                            <div className="col-12">
                                <button type="submit" className="btn btn-primary">Sign in</button>
                            </div>
                        </form>
                    </Example>

                    <Anchor tag="h2">
                        Horizontal Form
                    </Anchor>

                    <p>
                        Create horizontal forms with the grid by adding the <code>.row</code> class to form groups and
                        using the <code>.col-*-*</code> classes to specify the width of your labels and controls. Be
                        sure to add <code>.col-form-label</code> to your <code>&lt;label&gt;</code>s as well so they're
                        vertically centered with their associated form controls.
                    </p>

                    <p>
                        At times, you maybe need to use margin or padding utilities to create that perfect alignment you
                        need. For example, we've removed the <code>padding-top</code> on our stacked radio inputs label
                        to better align the text baseline.
                    </p>

                    <Example>
                        <form>
                            <div className="row mb-4">
                                <label htmlFor="inputEmail3" className="col-sm-2 col-form-label">Email</label>
                                <div className="col-sm-10">
                                    <input
                                        type="email"
                                        className="form-control"
                                        id="inputEmail3"
                                    />
                                </div>
                            </div>
                            <div className="row mb-4">
                                <label htmlFor="inputPassword3" className="col-sm-2 col-form-label">Password</label>
                                <div className="col-sm-10">
                                    <input
                                        type="password"
                                        className="form-control"
                                        id="inputPassword3"
                                    />
                                </div>
                            </div>
                            <fieldset className="row mb-4">
                                <legend className="col-form-label col-sm-2 pt-0">Radios</legend>
                                <div className="col-sm-10">
                                    <div className="form-check">
                                        <input
                                            className="form-check-input"
                                            type="radio"
                                            name="gridRadios"
                                            id="gridRadios1"
                                            defaultValue="option1"
                                            defaultChecked
                                        />
                                        <label className="form-check-label" htmlFor="gridRadios1">
                                            First radio
                                        </label>
                                    </div>
                                    <div className="form-check">
                                        <input
                                            className="form-check-input"
                                            type="radio"
                                            name="gridRadios"
                                            id="gridRadios2"
                                            defaultValue="option2"
                                        />
                                        <label className="form-check-label" htmlFor="gridRadios2">
                                            Second radio
                                        </label>
                                    </div>
                                    <div className="form-check disabled">
                                        <input
                                            className="form-check-input"
                                            type="radio"
                                            name="gridRadios"
                                            id="gridRadios3"
                                            defaultValue="option3"
                                            disabled
                                        />
                                        <label className="form-check-label" htmlFor="gridRadios3">
                                            Third disabled radio
                                        </label>
                                    </div>
                                </div>
                            </fieldset>
                            <div className="row mb-4">
                                <div className="col-sm-10 offset-sm-2">
                                    <div className="form-check">
                                        <input
                                            className="form-check-input"
                                            type="checkbox"
                                            id="gridCheck1"
                                        />
                                        <label className="form-check-label" htmlFor="gridCheck1">
                                            Example checkbox
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" className="btn btn-primary">Sign in</button>
                        </form>
                    </Example>

                    <Anchor tag="h3">
                        Horizontal Form Label Sizing
                    </Anchor>

                    <p>
                        Be sure to use <code>.col-form-label-sm</code> or <code>.col-form-label-lg</code> to
                        your <code>&lt;label&gt;</code>s or <code>&lt;legend&gt;</code>s to correctly follow the size
                        of <code>.form-control-lg</code> and <code>.form-control-sm</code>.
                    </p>

                    <Example>
                        <div className="row mb-4">
                            <label htmlFor="colFormLabelSm" className="col-sm-2 col-form-label col-form-label-sm">
                                Email
                            </label>
                            <div className="col-sm-10">
                                <input
                                    type="email"
                                    className="form-control form-control-sm"
                                    id="colFormLabelSm"
                                    placeholder="col-form-label-sm"
                                />
                            </div>
                        </div>
                        <div className="row mb-4">
                            <label htmlFor="colFormLabel" className="col-sm-2 col-form-label">
                                Email
                            </label>
                            <div className="col-sm-10">
                                <input
                                    type="email"
                                    className="form-control"
                                    id="colFormLabel"
                                    placeholder="col-form-label"
                                />
                            </div>
                        </div>
                        <div className="row">
                            <label htmlFor="colFormLabelLg" className="col-sm-2 col-form-label col-form-label-lg">
                                Email
                            </label>
                            <div className="col-sm-10">
                                <input
                                    type="email"
                                    className="form-control form-control-lg"
                                    id="colFormLabelLg"
                                    placeholder="col-form-label-lg"
                                />
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Column Sizing
                    </Anchor>

                    <p>
                        As shown in the previous examples, our grid system allows you to place any number
                        of <code>.col</code>s within a <code>.row</code>. They'll split the available width equally
                        between them. You may also pick a subset of your columns to take up more or less space, while
                        the remaining <code>.col</code>s equally split the rest, with specific column classes
                        like <code>.col-sm-7</code>.
                    </p>

                    <Example>
                        <div className="row g-4">
                            <div className="col-sm-7">
                                <input type="text" className="form-control" placeholder="City" aria-label="City" />
                            </div>
                            <div className="col-sm">
                                <input type="text" className="form-control" placeholder="State" aria-label="State" />
                            </div>
                            <div className="col-sm">
                                <input type="text" className="form-control" placeholder="Zip" aria-label="Zip" />
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Auto-sizing
                    </Anchor>

                    <p>
                        The example below uses a flexbox utility to vertically center the contents and
                        changes <code>.col</code> to <code>.col-auto</code> so that your columns only take up as much
                        space as needed. Put another way, the column sizes itself based on the contents.
                    </p>

                    <Example>
                        <form className="row g-4 align-items-center">
                            <div className="col-auto">
                                <label className="visually-hidden" htmlFor="autoSizingInput">Name</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="autoSizingInput"
                                    placeholder="Jane Doe"
                                />
                            </div>
                            <div className="col-auto">
                                <label className="visually-hidden" htmlFor="autoSizingInputGroup">Username</label>
                                <div className="input-group">
                                    <div className="input-group-text">@</div>
                                    <input
                                        type="text"
                                        className="form-control"
                                        id="autoSizingInputGroup"
                                        placeholder="Username"
                                    />
                                </div>
                            </div>
                            <div className="col-auto">
                                <label className="visually-hidden" htmlFor="autoSizingSelect">Preference</label>
                                <select className="form-select" id="autoSizingSelect">
                                    <option selected>Choose...</option>
                                    <option value="1">One</option>
                                    <option value="2">Two</option>
                                    <option value="3">Three</option>
                                </select>
                            </div>
                            <div className="col-auto">
                                <div className="form-check">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="autoSizingCheck"
                                    />
                                    <label className="form-check-label" htmlFor="autoSizingCheck">
                                        Remember me
                                    </label>
                                </div>
                            </div>
                            <div className="col-auto">
                                <button type="submit" className="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </Example>

                    <p>You can then remix that once again with size-specific column classes.</p>

                    <Example>
                        <form className="row g-4 align-items-center">
                            <div className="col-sm-3">
                                <label className="visually-hidden" htmlFor="specificSizeInputName">Name</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="specificSizeInputName"
                                    placeholder="Jane Doe"
                                />
                            </div>
                            <div className="col-sm-3">
                                <label className="visually-hidden" htmlFor="specificSizeInputGroupUsername">
                                    Username
                                </label>
                                <div className="input-group">
                                    <div className="input-group-text">@</div>
                                    <input
                                        type="text"
                                        className="form-control"
                                        id="specificSizeInputGroupUsername"
                                        placeholder="Username"
                                    />
                                </div>
                            </div>
                            <div className="col-sm-3">
                                <label className="visually-hidden" htmlFor="specificSizeSelect">Preference</label>
                                <select className="form-select" id="specificSizeSelect">
                                    <option selected>Choose...</option>
                                    <option value="1">One</option>
                                    <option value="2">Two</option>
                                    <option value="3">Three</option>
                                </select>
                            </div>
                            <div className="col-auto">
                                <div className="form-check">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="autoSizingCheck2"
                                    />
                                    <label className="form-check-label" htmlFor="autoSizingCheck2">
                                        Remember me
                                    </label>
                                </div>
                            </div>
                            <div className="col-auto">
                                <button type="submit" className="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </Example>

                    <Anchor tag="h2">
                        Inline Forms
                    </Anchor>

                    <p>
                        Use the <code>.row-cols-*</code> classes to create responsive horizontal layouts. By adding
                        gutter modifier classes, we'll have gutters in horizontal and vertical directions. On narrow
                        mobile viewports, the <code>.col-12</code> helps stack the form controls and more.
                        The <code>.align-items-center</code> aligns the form elements to the middle, making
                        the <code>.form-checkbox</code> align properly.
                    </p>

                    <Example>
                        <form className="row row-cols-lg-auto g-4 align-items-center">
                            <div className="col-12">
                                <label className="visually-hidden" htmlFor="inlineFormInputGroupUsername">
                                    Username
                                </label>
                                <div className="input-group">
                                    <div className="input-group-text">@</div>
                                    <input
                                        type="text"
                                        className="form-control"
                                        id="inlineFormInputGroupUsername"
                                        placeholder="Username"
                                    />
                                </div>
                            </div>

                            <div className="col-12">
                                <label className="visually-hidden" htmlFor="inlineFormSelectPref">Preference</label>
                                <select className="form-select" id="inlineFormSelectPref">
                                    <option selected>Choose...</option>
                                    <option value="1">One</option>
                                    <option value="2">Two</option>
                                    <option value="3">Three</option>
                                </select>
                            </div>

                            <div className="col-12">
                                <div className="form-check">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        id="inlineFormCheck"
                                    />
                                    <label className="form-check-label" htmlFor="inlineFormCheck">
                                        Remember me
                                    </label>
                                </div>
                            </div>

                            <div className="col-12">
                                <button type="submit" className="btn btn-primary">Submit</button>
                            </div>
                        </form>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
