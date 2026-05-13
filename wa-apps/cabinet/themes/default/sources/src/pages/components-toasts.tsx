import React from 'react';
import Layout from '../components/Layout';
import Article from '../components/Article';
import App from '../components/App';
import Anchor from '../components/Anchor';
import Example from '../components/Example';
import Image from "../components/Image";
import { url } from "../components/utils";

export default function() {
    const styles = [
        {key: 'sa-success', title: 'Success'},
        {key: 'sa-danger', title: 'Danger'},
        {key: 'sa-warning', title: 'Warning'},
        {key: 'sa-info', title: 'Info'},
        {key: 'sa-primary', title: 'Primary'},
        {key: 'sa-secondary', title: 'Secondary'},
        {key: 'sa-light', title: 'Light'},
        {key: 'sa-dark', title: 'Dark'},
    ];

    const image = <Image src="images/customers/customer-4.jpg" size={20} className="rounded me-3" />;

    const toast = (
        <div className="toast fade show" role="alert" aria-live="assertive" aria-atomic="true">
            <div className="toast-header">
                {image}
                <div className="me-auto fw-medium">Bootstrap</div>
                <small className="text-muted">11 mins ago</small>
                <button
                    type="button"
                    className="sa-close mt-n2 mb-n2 me-n3 ms-2"
                    data-bs-dismiss="toast"
                    aria-label="Close"
                />
            </div>
            <div className="toast-body">
                Hello, world! This is a toast message.
            </div>
        </div>
    );

    return (
        <Layout>
            <App>
                <Article
                    title="Toasts"
                    subtitle="Push notifications to your visitors with a toast, a lightweight and easily customizable alert message."
                    breadcrumb={[
                        {title: 'Dashboard', url: url('dashboard')},
                        {title: 'Components'},
                        {title: 'Toasts'},
                    ]}
                >
                    <Anchor tag="h2">
                        Basic Example
                    </Anchor>

                    <p>
                        To encourage extensible and predictable toasts, we recommend a header and body. Toast headers
                        use <code>display: flex</code>, allowing easy alignment of content thanks to our margin and
                        flexbox utilities.
                    </p>

                    <p>
                        Toasts are as flexible as you need and have very little required markup. At a minimum, we
                        require a single element to contain your "toasted" content and strongly encourage a dismiss
                        button.
                    </p>

                    <Example>
                        {toast}
                    </Example>

                    <Anchor tag="h2">
                        Live
                    </Anchor>

                    <p>
                        Click the button below to show a toast (positioned with our utilities in the lower right corner)
                        that has been hidden by default with <code>.hide</code>.
                    </p>

                    <Example>
                        <button type="button" className="btn btn-primary" id="liveToastBtn">
                            Show live toast
                        </button>

                        <div
                            className="toast fade hide"
                            data-bs-autohide="false"
                            role="alert"
                            aria-live="assertive"
                            aria-atomic="true"
                            id="liveToast"
                        >
                            <div className="toast-header">
                                {image}
                                <div className="me-auto fw-medium">Bootstrap</div>
                                <small className="text-muted">11 mins ago</small>
                                <button
                                    type="button"
                                    className="sa-close mt-n2 mb-n2 me-n3 ms-2"
                                    data-bs-dismiss="toast"
                                    aria-label="Close"
                                />
                            </div>
                            <div className="toast-body">
                                Hello, world! This is a toast message.
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Stacking
                    </Anchor>

                    <p>
                        You can stack toasts by wrapping them in a toast container, which will vertically add some
                        spacing.
                    </p>

                    <Example>
                        <div className="toast-container">
                            {toast}
                            {toast}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Custom content
                    </Anchor>

                    <p>
                        Customize your toasts by removing sub-components, tweaking them with utilities, or by adding
                        your own markup. Here we've created a simpler toast by removing the
                        default <code>.toast-header</code>, adding a custom hide button, and using some flexbox
                        utilities to adjust the layout.
                    </p>

                    <Example>
                        <div className="toast fade show align-items-center" role="alert" aria-live="assertive" aria-atomic="true">
                            <div className="d-flex">
                                <div className="toast-body">
                                    Hello, world! This is a toast message.
                                </div>
                                <button
                                    type="button"
                                    className="sa-close m-2 ms-auto"
                                    data-bs-dismiss="toast"
                                    aria-label="Close"
                                />
                            </div>
                        </div>
                    </Example>

                    <p>Alternatively, you can also add additional controls and components to toasts.</p>

                    <Example>
                        <div className="toast fade show" role="alert" aria-live="assertive" aria-atomic="true">
                            <div className="toast-body">
                                <div className="pb-2">
                                    Hello, world! This is a toast message.
                                </div>
                                <div className="mt-3 pt-4 border-top">
                                    <button type="button" className="btn btn-primary btn-sm">Take action</button>
                                    <button type="button" className="btn btn-secondary btn-sm ms-3" data-bs-dismiss="toast">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Color schemes
                    </Anchor>

                    <p>
                        There are a total of eight color options available. Choose a color that matches the context of
                        the toast.
                    </p>

                    <Example>
                        <div className="toast-container">
                            {styles.map((style) => (
                                <div key={style.key} className={`toast toast-${style.key} fade show`} role="alert" aria-live="assertive" aria-atomic="true">
                                    <div className="toast-header">
                                        {image}
                                        <div className="me-auto fw-medium">Bootstrap</div>
                                        <small>11 mins ago</small>
                                        <button
                                            type="button"
                                            className="sa-close mt-n2 mb-n2 me-n3 ms-2"
                                            data-bs-dismiss="toast"
                                            aria-label="Close"
                                        />
                                    </div>
                                    <div className="toast-body">
                                        Hello, world! This is a toast message.
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Example>

                    <Anchor tag="h2">
                        Placement
                    </Anchor>

                    <p>
                        Place toasts with custom CSS as you need them. The bottom right is often used for notifications,
                        as is the bottom middle.
                    </p>

                    <Example>
                        <form>
                            <div className="mb-4">
                                <label htmlFor="selectToastPlacement">Toast placement</label>
                                <select className="form-select mt-3" id="selectToastPlacement">
                                    <option value="" selected>Select a position...</option>
                                    <option value="top-0 start-0">Top left</option>
                                    <option value="top-0 start-50 translate-middle-x">Top center</option>
                                    <option value="top-0 end-0">Top right</option>
                                    <option value="top-50 start-0 translate-middle-y">Middle left</option>
                                    <option value="top-50 start-50 translate-middle">Middle center</option>
                                    <option value="top-50 end-0 translate-middle-y">Middle right</option>
                                    <option value="bottom-0 start-0">Bottom left</option>
                                    <option value="bottom-0 start-50 translate-middle-x">Bottom center</option>
                                    <option value="bottom-0 end-0">Bottom right</option>
                                </select>
                            </div>
                        </form>
                        <div
                            aria-live="polite"
                            aria-atomic="true"
                            className="bg-dark position-relative rounded-1 h-20x"
                        >
                            <div className="toast-container position-absolute p-4" id="toastPlacement">
                                <div className="toast fade show" role="alert" aria-live="assertive" aria-atomic="true">
                                    <div className="toast-header">
                                        {image}
                                        <div className="me-auto fw-medium">Bootstrap</div>
                                        <small className="text-muted">11 mins ago</small>
                                    </div>
                                    <div className="toast-body">
                                        Hello, world! This is a toast message.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Example>
                </Article>
            </App>
        </Layout>
    );
}
