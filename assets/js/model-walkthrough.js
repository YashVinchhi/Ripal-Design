(function () {
    const config = window.RD_WALKTHROUGH || {};
    const canvas = document.getElementById('walkthroughCanvas');
    const statusEl = document.getElementById('walkthroughStatus');
    const resetBtn = document.getElementById('walkReset');
    const fullscreenBtn = document.getElementById('walkFullscreen');

    if (!canvas || !window.BABYLON || !config.modelUrl) {
        if (statusEl) statusEl.textContent = 'Walkthrough viewer could not start.';
        return;
    }

    const PLAYER = {
        eyeHeight: 1.65,
        walkSpeed: 3.2,
        runSpeed: 6.4,
        mouseSensitivity: 0.0022,
        radius: 0.22,
        bodyHeight: 1.65
    };

    const state = {
        keys: Object.create(null),
        pointerLocked: false,
        startPosition: new BABYLON.Vector3(0, PLAYER.eyeHeight, -5),
        startTarget: BABYLON.Vector3.Zero(),
        walkY: PLAYER.eyeHeight,
        modelMin: null,
        modelMax: null
    };

    function setStatus(message) {
        if (statusEl) statusEl.textContent = message;
    }

    const engine = new BABYLON.Engine(canvas, true, {
        preserveDrawingBuffer: false,
        stencil: true,
        antialias: true
    });

    const scene = new BABYLON.Scene(engine);
    scene.clearColor = new BABYLON.Color4(0.05, 0.06, 0.08, 1);
    scene.collisionsEnabled = true;
    scene.gravity = BABYLON.Vector3.Zero();

    const camera = new BABYLON.UniversalCamera('walkCamera', state.startPosition.clone(), scene);
    camera.minZ = 0.04;
    camera.speed = 0;
    camera.inertia = 0;
    camera.fov = BABYLON.Tools.ToRadians(72);
    camera.applyGravity = false;
    camera.checkCollisions = true;
    camera.ellipsoid = new BABYLON.Vector3(PLAYER.radius, PLAYER.bodyHeight / 2, PLAYER.radius);
    camera.ellipsoidOffset = new BABYLON.Vector3(0, -PLAYER.bodyHeight / 2, 0);
    camera.inputs.clear();

    // Compatibility shim: some Babylon.js builds do not expose
    // `moveWithCollisions` on `UniversalCamera`. Provide a simple
    // fallback to avoid runtime errors. This fallback performs
    // basic translation; it does not attempt advanced collision
    // resolution but prevents the viewer from crashing.
    if (typeof camera.moveWithCollisions !== 'function') {
        camera.moveWithCollisions = function (displacement) {
            this.position.addInPlace(displacement);
        };
    }

    const hemi = new BABYLON.HemisphericLight('ambient', new BABYLON.Vector3(0, 1, 0), scene);
    hemi.intensity = 0.85;
    const sun = new BABYLON.DirectionalLight('sun', new BABYLON.Vector3(-0.4, -0.8, 0.35), scene);
    sun.intensity = 0.75;

    function keyName(event) {
        return String(event.code || event.key || '').toLowerCase();
    }

    window.addEventListener('keydown', function (event) {
        const key = keyName(event);
        if (['keyw', 'keys', 'keya', 'keyd', 'arrowup', 'arrowdown', 'arrowleft', 'arrowright', 'shiftleft', 'shiftright'].indexOf(key) !== -1) {
            event.preventDefault();
        }
        state.keys[key] = true;
    }, { passive: false });

    window.addEventListener('keyup', function (event) {
        state.keys[keyName(event)] = false;
    });

    document.addEventListener('pointerlockchange', function () {
        state.pointerLocked = document.pointerLockElement === canvas;
        setStatus(state.pointerLocked ? 'Mouse locked. WASD to walk, Shift to move faster, Esc to release.' : 'Click inside the viewer to control the walkthrough.');
    });

    window.addEventListener('mousemove', function (event) {
        if (!state.pointerLocked) {
            return;
        }

        camera.rotation.y += event.movementX * PLAYER.mouseSensitivity;
        camera.rotation.x += event.movementY * PLAYER.mouseSensitivity;
        const pitchLimit = BABYLON.Tools.ToRadians(86);
        camera.rotation.x = Math.max(-pitchLimit, Math.min(pitchLimit, camera.rotation.x));
    });

    function createInvisibleFloor(min, max) {
        const size = max.subtract(min);
        const floor = BABYLON.MeshBuilder.CreateBox('walkCollisionFloor', {
            width: Math.max(size.x + 20, 20),
            depth: Math.max(size.z + 20, 20),
            height: 0.08
        }, scene);
        floor.position = new BABYLON.Vector3((min.x + max.x) / 2, min.y - 0.04, (min.z + max.z) / 2);
        floor.isVisible = false;
        floor.checkCollisions = false;
    }

    function findStartPosition(min, max) {
        const center = min.add(max).scale(0.5);
        const size = max.subtract(min);
        const offset = Math.max(Math.min(size.z * 0.35, 8), 3);
        const y = min.y + PLAYER.eyeHeight;
        state.walkY = y;
        return {
            position: new BABYLON.Vector3(center.x, y, min.z - offset),
            target: new BABYLON.Vector3(center.x, y, center.z)
        };
    }

    function frameModel(meshes) {
        const validMeshes = meshes.filter((mesh) => mesh && mesh.getBoundingInfo && mesh.getTotalVertices && mesh.getTotalVertices() > 0);
        if (!validMeshes.length) {
            return;
        }

        validMeshes.forEach((mesh) => {
            mesh.computeWorldMatrix(true);
        });

        let min = validMeshes[0].getBoundingInfo().boundingBox.minimumWorld.clone();
        let max = validMeshes[0].getBoundingInfo().boundingBox.maximumWorld.clone();

        validMeshes.forEach((mesh) => {
            const box = mesh.getBoundingInfo().boundingBox;
            min = BABYLON.Vector3.Minimize(min, box.minimumWorld);
            max = BABYLON.Vector3.Maximize(max, box.maximumWorld);
            mesh.checkCollisions = true;
        });

        state.modelMin = min;
        state.modelMax = max;
        createInvisibleFloor(min, max);

        const start = findStartPosition(min, max);
        state.startPosition = start.position.clone();
        state.startTarget = start.target.clone();
        camera.position = state.startPosition.clone();
        camera.position.y = state.walkY;
        camera.setTarget(state.startTarget);
    }

    function movementVector() {
        let forwardInput = 0;
        let rightInput = 0;

        if (state.keys.keyw || state.keys.arrowup) forwardInput += 1;
        if (state.keys.keys || state.keys.arrowdown) forwardInput -= 1;
        if (state.keys.keyd || state.keys.arrowright) rightInput += 1;
        if (state.keys.keya || state.keys.arrowleft) rightInput -= 1;

        if (forwardInput === 0 && rightInput === 0) {
            return BABYLON.Vector3.Zero();
        }

        const yaw = camera.rotation.y;
        const forward = new BABYLON.Vector3(Math.sin(yaw), 0, Math.cos(yaw));
        const right = new BABYLON.Vector3(Math.cos(yaw), 0, -Math.sin(yaw));
        const move = forward.scale(forwardInput).add(right.scale(rightInput));
        move.normalize();
        return move;
    }

    async function loadModel() {
        setStatus('Loading 3D model...');
        try {
            const result = await BABYLON.SceneLoader.AppendAsync('', config.modelUrl, scene, function (evt) {
                if (!evt.lengthComputable) return;
                const pct = Math.round((evt.loaded / evt.total) * 100);
                setStatus('Loading 3D model... ' + pct + '%');
            });
            const meshes = scene.meshes || (result && result.meshes) || [];
            frameModel(meshes);
            setStatus('Click inside the viewer to control the walkthrough.');
        } catch (error) {
            console.error(error);
            setStatus('Could not load this GLB model. Check that the file is a valid binary glTF.');
        }
    }

    canvas.addEventListener('click', function () {
        canvas.focus();
        if (canvas.requestPointerLock) {
            canvas.requestPointerLock();
        }
    });

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            camera.position = state.startPosition.clone();
            camera.position.y = state.walkY;
            camera.setTarget(state.startTarget);
        });
    }

    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function () {
            const stage = canvas.closest('.walkthrough-stage') || canvas;
            if (!document.fullscreenElement && stage.requestFullscreen) {
                stage.requestFullscreen();
            } else if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        });
    }

    engine.runRenderLoop(function () {
        const delta = Math.min(engine.getDeltaTime() / 1000, 0.05);
        const speed = (state.keys.shiftleft || state.keys.shiftright) ? PLAYER.runSpeed : PLAYER.walkSpeed;
        const move = movementVector().scale(speed * delta);
        camera.position.y = state.walkY;
        if (move.lengthSquared() > 0) {
            camera.moveWithCollisions(move);
        }
        scene.render();
        camera.position.y = state.walkY;
    });

    window.addEventListener('resize', function () {
        engine.resize();
    });

    loadModel();
})();
