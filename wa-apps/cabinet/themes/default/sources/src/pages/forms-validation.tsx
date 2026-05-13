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
                    title="Validation"
                    subtitle="Provide valuable, actionable feedback to your users with HTML5 form validation, via browser default behaviors or custom styles and JavaScript."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Forms'},
                        {title: 'Validation'},
                    ]}
                >
                    <Anchor tag="h2">
                        Custom Styles
                    </Anchor>

                    <p>
                        For custom Bootstrap form validation messages, you'll need to add
                        the <code>novalidate</code> boolean attribute to your <code>&lt;form&gt;</code>. This disables
                        the browser default feedback tooltips, but still provides access to the form validation APIs in
                        JavaScript. Try to submit the form below; our JavaScript will intercept the submit button and
                        relay feedback to you. When attempting to submit, you'll see
                        the <code>:invalid</code> and <code>:valid</code> styles applied to your form controls.
                    </p>

                    <p>
                        Custom feedback styles apply custom colors, borders, and focus styles to better communicate
                        feedback.
                    </p>

                    <Example>
                        <form className="row g-4 needs-validation" noValidate>
                            <div className="col-md-4">
                                <label htmlFor="validationCustom01" className="form-label">First name</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="validationCustom01"
                                    defaultValue="Mark"
                                    required
                                />
                                <div className="valid-feedback">
                                    Looks good!
                                </div>
                            </div>
                            <div className="col-md-4">
                                <label htmlFor="validationCustom02" className="form-label">Last name</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="validationCustom02"
                                    defaultValue="Otto"
                                    required
                                />
                                <div className="valid-feedback">
                                    Looks good!
                                </div>
                            </div>
                            <div className="col-md-4">
                                <label htmlFor="validationCustomUsername" className="form-label">Username</label>
                                <div className="input-group has-validation">
                                    <span className="input-group-text" id="inputGroupPrepend">@</span>
                                    <input
                                        type="text"
                                        className="form-control"
                                        id="validationCustomUsername"
                                        aria-describedby="inputGroupPrepend"
                                        required
                                    />
                                    <div className="invalid-feedback">
                                        Please choose a username.
                                    </div>
                                </div>
                            </div>
                            <div className="col-md-6">
                                <label htmlFor="validationCustom03" className="form-label">City</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="validationCustom03"
                                    required
                                />
                                <div className="invalid-feedback">
                                    Please provide a valid city.
                                </div>
                            </div>
                            <div className="col-md-3">
                                <label htmlFor="validationCustom04" className="form-label">State</label>
                                <select className="form-select" id="validationCustom04" required>
                                    <option selected disabled value="">Choose...</option>
                                    <option>...</option>
                                </select>
                                <div className="invalid-feedback">
                                    Please select a valid state.
                                </div>
                            </div>
                            <div className="col-md-3">
                                <label htmlFor="validationCustom05" className="form-label">Zip</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="validationCustom05"
                                    required
                                />
                                <div className="invalid-feedback">
                                    Please provide a valid zip.
                                </div>
                            </div>
                            <div className="col-12">
                                <div className="form-check">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        defaultValue=""
                                        id="invalidCheck"
                                        required
                                    />
                                    <label className="form-check-label" htmlFor="invalidCheck">
                                        Agree to terms and conditions
                                    </label>
                                    <div className="invalid-feedback">
                                        You must agree before submitting.
                                    </div>
                                </div>
                            </div>
                            <div className="col-12">
                                <button className="btn btn-primary" type="submit">Submit form</button>
                            </div>
                        </form>
                    </Example>

                    <Anchor tag="h2">
                        Browser Defaults
                    </Anchor>

                    <p>
                        Not interested in custom validation feedback messages or writing JavaScript to change form
                        behaviors? All good, you can use the browser defaults. Try submitting the form below. Depending
                        on your browser and OS, you'll see a slightly different style of feedback.
                    </p>

                    <p>
                        While these feedback styles cannot be styled with CSS, you can still customize the feedback text
                        through JavaScript.
                    </p>

                    <Example>
                        <form className="row g-4">
                            <div className="col-md-4">
                                <label htmlFor="validationDefault01" className="form-label">First name</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="validationDefault01"
                                    defaultValue="Mark"
                                    required
                                />
                            </div>
                            <div className="col-md-4">
                                <label htmlFor="validationDefault02" className="form-label">Last name</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="validationDefault02"
                                    defaultValue="Otto"
                                    required
                                />
                            </div>
                            <div className="col-md-4">
                                <label htmlFor="validationDefaultUsername" className="form-label">Username</label>
                                <div className="input-group">
                                    <span className="input-group-text" id="inputGroupPrepend2">@</span>
                                    <input
                                        type="text"
                                        className="form-control"
                                        id="validationDefaultUsername"
                                        aria-describedby="inputGroupPrepend2"
                                        required
                                    />
                                </div>
                            </div>
                            <div className="col-md-6">
                                <label htmlFor="validationDefault03" className="form-label">City</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="validationDefault03"
                                    required
                                />
                            </div>
                            <div className="col-md-3">
                                <label htmlFor="validationDefault04" className="form-label">State</label>
                                <select className="form-select" id="validationDefault04" required>
                                    <option selected disabled value="">Choose...</option>
                                    <option>...</option>
                                </select>
                            </div>
                            <div className="col-md-3">
                                <label htmlFor="validationDefault05" className="form-label">Zip</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="validationDefault05"
                                    required
                                />
                            </div>
                            <div className="col-12">
                                <div className="form-check">
                                    <input
                                        className="form-check-input"
                                        type="checkbox"
                                        defaultValue=""
                                        id="invalidCheck2"
                                        required
                                    />
                                    <label className="form-check-label" htmlFor="invalidCheck2">
                                        Agree to terms and conditions
                                    </label>
                                </div>
                            </div>
                            <div className="col-12">
                                <button className="btn btn-primary" type="submit">Submit form</button>
                            </div>
                        </form>
                    </Example>

                    <Anchor tag="h2">
                        Server Side
                    </Anchor>

                    <p>
                        We recommend using client-side validation, but in case you require server-side validation, you
                        can indicate invalid and valid form fields
                        with <code>.is-invalid</code> and <code>.is-valid</code>. Note
                        that <code>.invalid-feedback</code> is also supported with these classes.
                    </p>

                    <p>
                        For invalid fields, ensure that the invalid feedback/error message is associated with the
                        relevant form field using <code>aria-describedby</code> (noting that this attribute allows more
                        than one <code>id</code> to be referenced, in case the field already points to additional form
                        text).
                    </p>

                    <Example>
                        <form className="row g-4">
                            <div className="col-md-4">
                                <label htmlFor="validationServer01" className="form-label">First name</label>
                                <input
                                    type="text"
                                    className="form-control is-valid"
                                    id="validationServer01"
                                    defaultValue="Mark"
                                    required
                                />
                                <div className="valid-feedback">
                                    Looks good!
                                </div>
                            </div>
                            <div className="col-md-4">
                                <label htmlFor="validationServer02" className="form-label">Last name</label>
                                <input
                                    type="text"
                                    className="form-control is-valid"
                                    id="validationServer02"
                                    defaultValue="Otto"
                                    required
                                />
                                <div className="valid-feedback">
                                    Looks good!
                                </div>
                            </div>
                            <div className="col-md-4">
                                <label htmlFor="validationServerUsername" className="form-label">Username</label>
                                <div className="input-group has-validation">
                                    <span className="input-group-text" id="inputGroupPrepend3">@</span>
                                    <input
                                        type="text"
                                        className="form-control is-invalid"
                                        id="validationServerUsername"
                                        aria-describedby="inputGroupPrepend3 validationServerUsernameFeedback"
                                        required
                                    />
                                    <div id="validationServerUsernameFeedback" className="invalid-feedback">
                                        Please choose a username.
                                    </div>
                                </div>
                            </div>
                            <div className="col-md-6">
                                <label htmlFor="validationServer03" className="form-label">City</label>
                                <input
                                    type="text"
                                    className="form-control is-invalid"
                                    id="validationServer03"
                                    aria-describedby="validationServer03Feedback"
                                    required
                                />
                                <div id="validationServer03Feedback" className="invalid-feedback">
                                    Please provide a valid city.
                                </div>
                            </div>
                            <div className="col-md-3">
                                <label htmlFor="validationServer04" className="form-label">State</label>
                                <select
                                    className="form-select is-invalid"
                                    id="validationServer04"
                                    aria-describedby="validationServer04Feedback"
                                    required
                                >
                                    <option selected disabled value="">Choose...</option>
                                    <option>...</option>
                                </select>
                                <div id="validationServer04Feedback" className="invalid-feedback">
                                    Please select a valid state.
                                </div>
                            </div>
                            <div className="col-md-3">
                                <label htmlFor="validationServer05" className="form-label">Zip</label>
                                <input
                                    type="text"
                                    className="form-control is-invalid"
                                    id="validationServer05"
                                    aria-describedby="validationServer05Feedback"
                                    required
                                />
                                <div id="validationServer05Feedback" className="invalid-feedback">
                                    Please provide a valid zip.
                                </div>
                            </div>
                            <div className="col-12">
                                <div className="form-check">
                                    <input
                                        className="form-check-input is-invalid"
                                        type="checkbox"
                                        defaultValue=""
                                        id="invalidCheck3"
                                        aria-describedby="invalidCheck3Feedback"
                                        required
                                    />
                                    <label className="form-check-label" htmlFor="invalidCheck3">
                                        Agree to terms and conditions
                                    </label>
                                    <div id="invalidCheck3Feedback" className="invalid-feedback">
                                        You must agree before submitting.
                                    </div>
                                </div>
                            </div>
                            <div className="col-12">
                                <button className="btn btn-primary" type="submit">Submit form</button>
                            </div>
                        </form>
                    </Example>

                    <Anchor tag="h2">
                        Supported Elements
                    </Anchor>

                    <p>Validation styles are available for the following form controls and components:</p>

                    <ul>
                        <li>
                            <code>&lt;input&gt;</code>s and <code>&lt;textarea&gt;</code>s
                            with <code>.form-control</code> (including up to one <code>.form-control</code> in input
                            groups)
                        </li>
                        <li><code>&lt;select&gt;</code>s with <code>.form-select</code></li>
                        <li><code>.form-check</code>s</li>
                    </ul>

                    <Example>
                        <form className="was-validated">
                            <div className="mb-4">
                                <label htmlFor="validationTextarea" className="form-label">Textarea</label>
                                <textarea
                                    className="form-control is-invalid"
                                    id="validationTextarea"
                                    placeholder="Required example textarea"
                                    required
                                />
                                <div className="invalid-feedback">
                                    Please enter a message in the textarea.
                                </div>
                            </div>

                            <div className="form-check mb-4">
                                <input
                                    type="checkbox"
                                    className="form-check-input"
                                    id="validationFormCheck1"
                                    required
                                />
                                <label className="form-check-label" htmlFor="validationFormCheck1">
                                    Check this checkbox
                                </label>
                                <div className="invalid-feedback">Example invalid feedback text</div>
                            </div>

                            <div className="form-check">
                                <input
                                    type="radio"
                                    className="form-check-input"
                                    id="validationFormCheck2"
                                    name="radio-stacked"
                                    required
                                />
                                <label className="form-check-label" htmlFor="validationFormCheck2">
                                    Toggle this radio
                                </label>
                            </div>
                            <div className="form-check mb-4">
                                <input
                                    type="radio"
                                    className="form-check-input"
                                    id="validationFormCheck3"
                                    name="radio-stacked"
                                    required
                                />
                                <label className="form-check-label" htmlFor="validationFormCheck3">
                                    Or toggle this other radio
                                </label>
                                <div className="invalid-feedback">More example invalid feedback text</div>
                            </div>

                            <div className="mb-4">
                                <select className="form-select" required aria-label="select example">
                                    <option value="">Open this select menu</option>
                                    <option value="1">One</option>
                                    <option value="2">Two</option>
                                    <option value="3">Three</option>
                                </select>
                                <div className="invalid-feedback">Example invalid select feedback</div>
                            </div>

                            <div className="mb-4">
                                <input
                                    type="file"
                                    className="form-control"
                                    aria-label="file example"
                                    required
                                />
                                <div className="invalid-feedback">Example invalid form file feedback</div>
                            </div>

                            <div>
                                <button className="btn btn-primary" type="submit" disabled>Submit form</button>
                            </div>
                        </form>
                    </Example>

                    <Anchor tag="h2">
                        Tooltips
                    </Anchor>

                    <p>
                        If your form layout allows it, you can swap the <code>.{'{'}valid | invalid{'}'}-feedback</code> classes
                        for <code>.{'{'}valid | invalid{'}'}-tooltip</code> classes to display validation feedback in a styled
                        tooltip. Be sure to have a parent with <code>position: relative</code> on it for tooltip
                        positioning. In the example below, our column classes have this already, but your project may
                        require an alternative setup.
                    </p>

                    <Example>
                        <form className="row g-4 needs-validation" noValidate>
                            <div className="col-md-4 position-relative">
                                <label htmlFor="validationTooltip01" className="form-label">First name</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="validationTooltip01"
                                    defaultValue="Mark"
                                    required
                                />
                                <div className="valid-tooltip">
                                    Looks good!
                                </div>
                            </div>
                            <div className="col-md-4 position-relative">
                                <label htmlFor="validationTooltip02" className="form-label">Last name</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="validationTooltip02"
                                    defaultValue="Otto"
                                    required
                                />
                                <div className="valid-tooltip">
                                    Looks good!
                                </div>
                            </div>
                            <div className="col-md-4 position-relative">
                                <label htmlFor="validationTooltipUsername" className="form-label">Username</label>
                                <div className="input-group has-validation">
                                    <span className="input-group-text" id="validationTooltipUsernamePrepend">@</span>
                                    <input
                                        type="text"
                                        className="form-control"
                                        id="validationTooltipUsername"
                                        aria-describedby="validationTooltipUsernamePrepend"
                                        required
                                    />
                                    <div className="invalid-tooltip">
                                        Please choose a unique and valid username.
                                    </div>
                                </div>
                            </div>
                            <div className="col-md-6 position-relative">
                                <label htmlFor="validationTooltip03" className="form-label">City</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="validationTooltip03"
                                    required
                                />
                                <div className="invalid-tooltip">
                                    Please provide a valid city.
                                </div>
                            </div>
                            <div className="col-md-3 position-relative">
                                <label htmlFor="validationTooltip04" className="form-label">State</label>
                                <select className="form-select" id="validationTooltip04" required>
                                    <option selected disabled value="">Choose...</option>
                                    <option>...</option>
                                </select>
                                <div className="invalid-tooltip">
                                    Please select a valid state.
                                </div>
                            </div>
                            <div className="col-md-3 position-relative">
                                <label htmlFor="validationTooltip05" className="form-label">Zip</label>
                                <input
                                    type="text"
                                    className="form-control"
                                    id="validationTooltip05"
                                    required
                                />
                                <div className="invalid-tooltip">
                                    Please provide a valid zip.
                                </div>
                            </div>
                            <div className="col-12">
                                <button className="btn btn-primary" type="submit">Submit form</button>
                            </div>
                        </form>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
