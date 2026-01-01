export class InputHandler {
    constructor() {
        this.modal = null;
        this.resolveInput = null;
    }

    create() {
        if (document.getElementById("pyviz-input-modal")) return;

        this.modal = document.createElement("div");
        this.modal.id = "pyviz-input-modal";
        this.modal.style.cssText = `
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        `;

        const dialog = document.createElement("div");
        dialog.style.cssText = `
            background: #252526;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #454545;
            min-width: 300px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            color: #ccc;
            font-family: sans-serif;
        `;

        const title = document.createElement("h3");
        title.textContent = "Input Required";
        title.style.margin = "0 0 10px 0";
        title.style.fontSize = "14px";
        title.style.color = "#fff";

        this.promptText = document.createElement("p");
        this.promptText.style.fontSize = "13px";
        this.promptText.style.marginBottom = "10px";

        this.input = document.createElement("input");
        this.input.type = "text";
        this.input.style.cssText = `
            width: 100%;
            padding: 8px;
            background: #3c3c3c;
            border: 1px solid #555;
            color: white;
            border-radius: 4px;
            margin-bottom: 10px;
            box-sizing: border-box;
        `;
        // enter key submission
        this.input.addEventListener("keydown", (e) => {
            if (e.key === "Enter") this.submit();
        });

        const btnContainer = document.createElement("div");
        btnContainer.style.textAlign = "right";

        const submitBtn = document.createElement("button");
        submitBtn.textContent = "Submit";
        submitBtn.style.cssText = `
            background: #007acc;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 3px;
            cursor: pointer;
        `;
        submitBtn.onclick = () => this.submit();

        btnContainer.appendChild(submitBtn);
        dialog.appendChild(title);
        dialog.appendChild(this.promptText);
        dialog.appendChild(this.input);
        dialog.appendChild(btnContainer);
        this.modal.appendChild(dialog);

        document.body.appendChild(this.modal);
    }

    requestInput(prompt) {
        if (!this.modal) this.create();
        return new Promise((resolve) => {
            this.resolveInput = resolve;
            this.promptText.textContent = prompt || "Please enter a value:";
            this.input.value = "";
            this.modal.style.display = "flex";
            this.input.focus();
        });
    }

    submit() {
        if (this.resolveInput) {
            const value = this.input.value;
            this.modal.style.display = "none";
            this.resolveInput(value);
            this.resolveInput = null;
        }
    }
}
