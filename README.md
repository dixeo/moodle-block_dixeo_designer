![Dixeo logo](pix/dixeo-logo.png)

# Dixeo Course Designer for Moodle LMS

Effortlessly create complete Moodle courses using the power of AI. The **Dixeo Course Designer** helps administrators and educators turn a simple course description and optional supporting files into a fully functional, ready-to-use Moodle course in just a few clicks.

This plugin generates your course on Dixeo.com and automatically configures your Moodle platform to access it via LTI.

You can also create a [free Dixeo account](https://dixeo.com/login/signup.php) to start building courses directly.

**Developers:** plugin layout (PHP namespaces, externals, AMD modules) is documented in [`classes/README.md`](classes/README.md) and [`amd/README.md`](amd/README.md). Ongoing quality work is tracked in [`docs/QUALITY.md`](docs/QUALITY.md).

---

## 🚀 Features

- **AI-Powered Course Generation**  
  Automatically generate structured courses based on a short description.

- **File Upload Support**  
  Enrich the course design by uploading relevant source files (e.g., PDFs, docs).

- **Automatic LTI Module Integration**  
  The plugin sets up LTI modules for seamless access to the generated content.

- **Multi-language Support**  
  Available in **English**, **French**, **Spanish**, and **Italian**.

---

## ⚙️ Installation

1. Download the plugin ZIP file.
2. Extract and place the folder named `dixeo_designer` into the `/blocks` directory of your Moodle installation.
3. Log in to your Moodle site as an administrator.
4. Navigate to **Site administration** — Moodle will detect the new plugin and walk you through the installation steps.

---

## 🔧 Configuration

![Dixeo settings](pix/dixeo-screen2.png)

1. Go to **Site administration** > **Plugins** > **Blocks** > **Dixeo Course Designer**.
2. Enter the **API key** provided by Dixeo.
3. Specify the **default category name** where generated courses will be created.
4. Ensure the **LTI enrolment method** is enabled on your Moodle platform.

---

## 🧑‍🏫 How to Use

![Dixeo settings](pix/dixeo-screen1.png)

1. Add the **Dixeo Course Designer** block to your Moodle dashboard or course page.
2. Provide a **course description** in the input box.
3. *(Optional)* Upload any supporting documents to enhance content generation.
4. Click the **Generate** button.
5. Wait a few moments for the course to be created.
6. A **success message** will appear with a link to the newly generated course.

---

## 🔐 Permissions

Ensure that users have the `local/dixeo:create` capability to access and use the plugin features.

---

## 🆘 Support

For assistance, questions, or further information, please contact **contact@dixeo.com** or refer to the official plugin documentation.

---

*Created and maintained by [Dixeo](https://www.dixeo.com/)*
