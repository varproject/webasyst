import React from 'react';
import Layout from '../components/Layout';
import App from '../components/App';
import classnames from 'classnames';
import Image from "../components/Image";
import PageHeader from "../components/PageHeader";
import { url } from "../components/utils";

type CardProps = {
    title?: string;
    help?: string;
    className?: string;
    body?: React.ReactNode;
    children?: React.ReactNode;
};

const Card = ({title, help, children, body, className}: CardProps) => (
    <div className={classnames('card', className)}>
        <div className="card-body p-5">
            {title && (
                <div className="mb-5">
                    <h2 className="mb-0 fs-exact-18">{title}</h2>
                    {help && <div className="mt-3 text-muted">{help}</div>}
                </div>
            )}
            {children}
        </div>
        {body}
    </div>
);

export default function() {
    const category = {
        name: 'Hand Tools',
        slug: 'hand-tools',
        description: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur ornare, mi in ornare elementum, libero nibh lacinia urna, quis convallis lorem erat at purus. Maecenas eu varius nisi.',
        image: 'images/products/product-7.jpg',
    };

    const main = (
        <>
            <Card title="Basic information">
                <div className="mb-4">
                    <label htmlFor="form-category/name" className="form-label">
                        Name
                    </label>
                    <input type="text" className="form-control" id="form-category/name" defaultValue={category.name} />
                </div>
                <div className="mb-4">
                    <label htmlFor="form-category/slug" className="form-label">
                        Slug
                    </label>
                    <div className="input-group input-group--sa-slug">
                        <span className="input-group-text" id="form-category/slug-addon">
                            https://example.com/catalog/
                        </span>
                        <input
                            type="text"
                            className="form-control"
                            id="form-category/slug"
                            aria-describedby="form-category/slug-addon form-category/slug-help"
                            defaultValue={category.slug}
                        />
                    </div>
                    <div id="form-category/slug-help" className="form-text">
                        Unique human-readable category identifier. No longer than 255 characters.
                    </div>
                </div>
                <div className="mb-4">
                    <label htmlFor="form-category/description" className="form-label">
                        Description
                    </label>
                    <textarea
                        id="form-category/description"
                        className="sa-quill-control form-control"
                        rows={8}
                        defaultValue={category.description}
                    />
                </div>
            </Card>

            <Card
                title="Search engine optimization"
                help="Provide information that will help improve the snippet and bring your category to the top of search engines."
                className="mt-5"
            >
                <div className="mb-4">
                    <label htmlFor="form-category/seo-title" className="form-label">
                        Page title
                    </label>
                    <input type="text" className="form-control" id="form-category/seo-title" />
                </div>
                <div>
                    <label htmlFor="form-category/seo-description" className="form-label">
                        Meta description
                    </label>
                    <textarea
                        id="form-category/seo-description"
                        className="form-control"
                        rows={2}
                    />
                </div>
            </Card>
        </>
    );

    const sidebar = (
        <>
            <Card title="Visibility" className="w-100">
                <div className="mb-4">
                    <label className="form-check">
                        <input type="radio" className="form-check-input" name="status" />
                        <span className="form-check-label">Published</span>
                    </label>
                    <label className="form-check">
                        <input type="radio" className="form-check-input" name="status" defaultChecked />
                        <span className="form-check-label">Scheduled</span>
                    </label>
                    <label className="form-check mb-0">
                        <input type="radio" className="form-check-input" name="status" />
                        <span className="form-check-label">Hidden</span>
                    </label>
                </div>
                <div>
                    <label htmlFor="form-category/seo-title" className="form-label">
                        Publish date
                    </label>
                    <input
                        type="text"
                        className="form-control datepicker-here"
                        id="form-category/publish-date"
                        data-auto-close="true"
                        data-language="en"
                    />
                    <div className="form-text">
                        The category will not be visible until the specified date.
                    </div>
                </div>
            </Card>
            <Card title="Parent category" className="w-100 mt-5">
                <select className="sa-select2 form-select">
                    <option>[None]</option>
                    <option selected>Tools</option>
                    <option>Screwdrivers</option>
                    <option>Chainsaws</option>
                    <option>Hand tools</option>
                    <option>Machine tools</option>
                    <option>Power machinery</option>
                    <option>Measurements</option>
                    <option>Power tools</option>
                </select>
                <div className="form-text">
                    Select a category that will be the parent of the current one.
                </div>
            </Card>
            <Card title="Image" className="w-100 mt-5">
                <div className="border p-4 d-flex justify-content-center">
                    <div className="max-w-20x">
                        <Image src={category.image} size={16 * 20} className="w-100 h-auto" />
                    </div>
                </div>
                <div className="mt-4 mb-n2">
                    <a href="#" className="me-3 pe-2">Replace image</a>
                    <a href="#" className="text-danger me-3 pe-2">Remove image</a>
                </div>
            </Card>
        </>
    );

    return (
        <Layout>
            <App>
                <div className="mx-sm-2 px-2 px-sm-3 px-xxl-4 pb-6">
                    <div className="container container--max--xl">
                        <PageHeader
                            title="Edit Category"
                            actions={[
                                <a key="duplicate" href="#" className="btn btn-secondary me-3">
                                    Duplicate
                                </a>,
                                <a key="save" href="#" className="btn btn-primary">
                                    Save
                                </a>,
                            ]}
                            breadcrumb={[
                                {title: 'Dashboard', url: url('dashboard')},
                                {title: 'Categories', url: url('categories-list')},
                                {title: 'Edit Category', url: url('category')},
                            ]}
                        />
                        <div
                            className="sa-entity-layout"
                            data-sa-container-query={JSON.stringify({920: 'sa-entity-layout--size--md', 1100: 'sa-entity-layout--size--lg'})}
                        >
                            <div className="sa-entity-layout__body">
                                <div className="sa-entity-layout__main">
                                    {main}
                                </div>
                                <div className="sa-entity-layout__sidebar">
                                    {sidebar}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </App>
        </Layout>
    );
}
